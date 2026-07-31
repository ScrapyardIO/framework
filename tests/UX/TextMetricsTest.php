<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\NutsAndBolts\Geometry\Alignment;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\Rendering\Fonts\GFXFont;
use Fabricate\UX\Surface;
use Fabricate\UX\TextMetrics;
use Microscrap\GFX\PhpdaFruit\PhpdafruitGfx;
use PHPUnit\Framework\TestCase;

/**
 * Intrinsic text sizing, and the baseline correction that makes it usable.
 *
 * Custom GFX fonts report bounds relative to the baseline cursor, so feeding a
 * box position straight to setCursor() places text wrong by a font-dependent
 * amount. Every sketch in this repo currently open-codes the `- $bounds['x1']`
 * fix; these cases pin it in one place instead.
 *
 * Asserted by actually printing and reading back where the ink landed, because
 * the arithmetic agreeing with itself proves nothing.
 */
class TextMetricsTest extends TestCase
{
    protected const int WIDTH = 32;

    protected const int HEIGHT = 16;

    public function testAClassicFontNeedsNoCorrection(): void
    {
        [$surface] = $this->surface();

        $metrics = TextMetrics::of($surface, 'AB');

        $this->assertSame([12, 8], [$metrics->size->width, $metrics->size->height]);
        $this->assertSame([0, 0], [$metrics->offset->x, $metrics->offset->y]);
    }

    /**
     * The glyph sits two rows above the baseline, so the cursor has to be two
     * rows below where the ink is meant to start.
     */
    public function testACustomFontReportsItsBaselineOffset(): void
    {
        [$surface, $renderer] = $this->surface();
        $renderer->setFont($this->baselineFont());

        $metrics = TextMetrics::of($surface, 'A');

        $this->assertSame([2, 2], [$metrics->size->width, $metrics->size->height]);
        $this->assertSame([0, 2], [$metrics->offset->x, $metrics->offset->y]);
    }

    /**
     * Criterion: baseline-relative text is centred correctly. The ink, not the
     * cursor, must end up in the middle of the box.
     */
    public function testCentringPutsTheInkInTheMiddleOfTheBox(): void
    {
        [$surface, $renderer, $buffer] = $this->surface();
        $renderer->setFont($this->baselineFont());
        $renderer->setTextColor(1);

        $metrics = TextMetrics::of($surface, 'A');
        $cursor = $metrics->cursorIn(new Rect(0, 0, self::WIDTH, self::HEIGHT));

        $this->assertSame([15, 9], [$cursor->x, $cursor->y], 'The cursor sits below the visual box by the baseline offset.');

        $surface->paint(function () use ($surface, $cursor): void {
            $surface->setCursor($cursor->x, $cursor->y)->print('A');
        });

        // A 2x2 glyph centred in 32x16 occupies x 15..16, y 7..8.
        $this->assertSame(
            [15, 7, 16, 8],
            UxHarness::paintedBounds($buffer, self::WIDTH, self::HEIGHT)?->toBounds(),
        );
    }

    /**
     * The counterpart: without the correction the same text lands two rows high,
     * which is exactly the bug the offset exists to prevent.
     */
    public function testIgnoringTheOffsetMissesTheBox(): void
    {
        [$surface, $renderer, $buffer] = $this->surface();
        $renderer->setFont($this->baselineFont());
        $renderer->setTextColor(1);

        $metrics = TextMetrics::of($surface, 'A');
        $placed = Alignment::center()->positionIn(new Rect(0, 0, self::WIDTH, self::HEIGHT), $metrics->size);

        $surface->paint(function () use ($surface, $placed): void {
            $surface->setCursor($placed->x, $placed->y)->print('A');
        });

        $this->assertSame(
            [15, 5, 16, 6],
            UxHarness::paintedBounds($buffer, self::WIDTH, self::HEIGHT)?->toBounds(),
            'Using the visual position as the cursor should be wrong by the baseline offset.',
        );
    }

    public function testAlignmentIsHonouredRatherThanAlwaysCentring(): void
    {
        [$surface, $renderer] = $this->surface();
        $renderer->setFont($this->baselineFont());

        $metrics = TextMetrics::of($surface, 'A');
        $cursor = $metrics->cursorIn(new Rect(0, 0, self::WIDTH, self::HEIGHT), Alignment::bottomRight());

        $this->assertSame([30, 16], [$cursor->x, $cursor->y]);
    }

    /**
     * Metrics are measured through a node's own surface, so they come back in
     * local coordinates and can be handed straight back to setCursor().
     */
    public function testMetricsAreLocalToTheSurfaceThatMeasuredThem(): void
    {
        [, $renderer, $buffer] = $this->surface();
        $renderer->setFont($this->baselineFont());
        $renderer->setTextColor(1);

        $inset = Surface::forNode($renderer, new Rect(6, 4, 20, 10), new Rect(0, 0, self::WIDTH, self::HEIGHT));
        $metrics = TextMetrics::of($inset, 'A');
        $cursor = $metrics->cursorIn($inset->bounds());

        $this->assertSame([9, 6], [$cursor->x, $cursor->y], 'The cursor must be in the node\'s own coordinates.');

        $inset->paint(function () use ($inset, $cursor): void {
            $inset->setCursor($cursor->x, $cursor->y)->print('A');
        });

        // The node sits at 6,4, so its centred glyph lands there plus 9,4.
        $this->assertSame(
            [15, 8, 16, 9],
            UxHarness::paintedBounds($buffer, self::WIDTH, self::HEIGHT)?->toBounds(),
        );
    }

    /**
     * A 2x2 solid glyph for 'A', two rows above the baseline — the smallest font
     * that still exhibits the baseline offset.
     */
    protected function baselineFont(): GFXFont
    {
        return new class extends GFXFont
        {
            protected int $first = 65;

            protected int $last = 65;

            protected int $yAdvance = 10;

            protected string $fontEncoding = 'adafruit';

            /**
             * @var array<int, int>
             */
            protected array $bitmaps = [0xF0];

            /**
             * @var array<int, array<int, int>>
             */
            protected array $glyphs = [
                [0, 2, 2, 3, 0, -2],
            ];
        };
    }

    /**
     * @return array{0: Surface, 1: PhpdafruitGfx, 2: DirtyRegionsBuffer}
     */
    protected function surface(): array
    {
        $buffer = new DirtyRegionsBuffer(self::WIDTH, self::HEIGHT, UxHarness::spec());
        $renderer = new PhpdafruitGfx($buffer);

        return [Surface::root($renderer, self::WIDTH, self::HEIGHT), $renderer, $buffer];
    }
}
