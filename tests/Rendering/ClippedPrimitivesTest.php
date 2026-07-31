<?php

namespace DeptOfScrapyardRobotics\Tests\Rendering;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\Endianness;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\Framebuffers\Strategy\FormatSpecFramebuffer;
use Fabricate\Framebuffers\Strategy\FullFramebuffer;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Microscrap\GFX\PhpdaFruit\PhpdafruitGfx;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Clipping has to hold for *every* primitive family, not just the ones routed
 * through the obvious funnel — several fills reach the buffer through their own
 * internal helpers. Each case below deliberately overdraws the clip on all
 * sides, then proves nothing was written outside it.
 *
 * Enforcement is also a throughput requirement: a stray pixel reaching a
 * dirty-tracking buffer marks a region dirty, and on an I2C OLED one spurious
 * dirty page costs a real 20-30ms transmit.
 */
class ClippedPrimitivesTest extends TestCase
{
    protected const int SURFACE = 32;

    protected const int COLOR = 0xFFFF;

    /**
     * @return array<string, array{0: callable(PhpdafruitGfx): void}>
     */
    public static function overdrawingPrimitives(): array
    {
        $span = self::SURFACE;
        $color = self::COLOR;

        return [
            'drawPixel' => [fn (PhpdafruitGfx $gfx) => $gfx
                ->drawPixel(0, 0, $color)
                ->drawPixel(10, 10, $color)
                ->drawPixel(31, 31, $color)],
            'drawPixels' => [fn (PhpdafruitGfx $gfx) => $gfx->drawPixels([
                [1, 1, $color], [12, 12, $color], [30, 30, $color],
            ])],
            'drawLine' => [fn (PhpdafruitGfx $gfx) => $gfx->drawLine(0, 0, $span - 1, $span - 1, $color)],
            'drawLines' => [fn (PhpdafruitGfx $gfx) => $gfx->drawLines([
                [0, 0, $span - 1, $span - 1, $color],
                [0, $span - 1, $span - 1, 0, $color],
            ])],
            'drawHorizontalLine' => [fn (PhpdafruitGfx $gfx) => $gfx->drawHorizontalLine(0, 12, $span, $color)],
            'drawVerticalLine' => [fn (PhpdafruitGfx $gfx) => $gfx->drawVerticalLine(12, 0, $span, $color)],
            // Outline primitives are placed so their *edges* straddle the clip
            // boundary; a shape merely enclosing the clip would draw nothing
            // inside it and prove nothing.
            'drawRect' => [fn (PhpdafruitGfx $gfx) => $gfx->drawRect(2, 2, 20, 20, $color)],
            'fillRect' => [fn (PhpdafruitGfx $gfx) => $gfx->fillRect(0, 0, $span, $span, $color)],
            'drawRoundRect' => [fn (PhpdafruitGfx $gfx) => $gfx->drawRoundRect(2, 2, 20, 20, 5, $color)],
            'fillRoundRect' => [fn (PhpdafruitGfx $gfx) => $gfx->fillRoundRect(0, 0, $span, $span, 5, $color)],
            'drawCircle' => [fn (PhpdafruitGfx $gfx) => $gfx->drawCircle(8, 8, 10, $color)],
            'fillCircle' => [fn (PhpdafruitGfx $gfx) => $gfx->fillCircle(16, 16, 15, $color)],
            'drawEllipse' => [fn (PhpdafruitGfx $gfx) => $gfx->drawEllipse(8, 8, 12, 10, $color)],
            'fillEllipse' => [fn (PhpdafruitGfx $gfx) => $gfx->fillEllipse(16, 16, 15, 12, $color)],
            'drawTriangle' => [fn (PhpdafruitGfx $gfx) => $gfx->drawTriangle(0, $span - 1, 16, 0, $span - 1, $span - 1, $color)],
            'fillTriangle' => [fn (PhpdafruitGfx $gfx) => $gfx->fillTriangle(0, $span - 1, 16, 0, $span - 1, $span - 1, $color)],
            'fill' => [fn (PhpdafruitGfx $gfx) => $gfx->fill($color)],
            'text' => [fn (PhpdafruitGfx $gfx) => $gfx
                ->setTextColor($color)
                ->setTextSize(2)
                ->setCursor(0, 6)
                ->print('MMMMMMMM')],
        ];
    }

    /**
     * @param  callable(PhpdafruitGfx): void  $paint
     */
    #[DataProvider('overdrawingPrimitives')]
    public function testPrimitivesNeverWriteOutsideTheClip(callable $paint): void
    {
        $clip = $this->clipRegion();
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $gfx = new PhpdafruitGfx($buffer);

        $gfx->pushClip($clip);
        $paint($gfx);

        $painted = $this->paintedBounds($buffer);

        $this->assertNotNull($painted, 'The primitive painted nothing at all, so the assertion would pass vacuously.');
        $this->assertTrue(
            $clip->containsRect($painted),
            "Painted [{$this->describe($painted)}] escapes the clip [{$this->describe($clip)}].",
        );
    }

    /**
     * @param  callable(PhpdafruitGfx): void  $paint
     */
    #[DataProvider('overdrawingPrimitives')]
    public function testPrimitivesNeverMarkDirtyOutsideTheClip(callable $paint): void
    {
        $clip = $this->clipRegion();
        $buffer = new DirtyRegionsBuffer(self::SURFACE, self::SURFACE, $this->spec());
        $gfx = new PhpdafruitGfx($buffer);

        $gfx->pushClip($clip);
        $paint($gfx);

        $updates = $buffer->dump();

        $this->assertNotSame([], $updates, 'Nothing was marked dirty, so the assertion would pass vacuously.');

        foreach ($updates as $update) {
            $dirty = new Rect($update->origin_x, $update->origin_y, $update->width, $update->height);

            $this->assertTrue(
                $clip->containsRect($dirty),
                "Dirty region [{$this->describe($dirty)}] escapes the clip [{$this->describe($clip)}].",
            );
        }
    }

    public function testUnclippedDrawingStillCoversTheWholeSurface(): void
    {
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());

        (new PhpdafruitGfx($buffer))->fillRect(0, 0, self::SURFACE, self::SURFACE, self::COLOR);

        $this->assertSame(
            [0, 0, self::SURFACE - 1, self::SURFACE - 1],
            $this->paintedBounds($buffer)?->toBounds(),
        );
    }

    public function testNestedClipsConfinePaintingToTheOverlap(): void
    {
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $gfx = new PhpdafruitGfx($buffer);

        $gfx->pushClip(new Rect(4, 4, 16, 16));
        $gfx->pushClip(new Rect(12, 12, 16, 16));
        $gfx->fillRect(0, 0, self::SURFACE, self::SURFACE, self::COLOR);

        $this->assertSame([12, 12, 19, 19], $this->paintedBounds($buffer)?->toBounds());
    }

    public function testPoppingAClipRestoresTheWiderRegion(): void
    {
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $gfx = new PhpdafruitGfx($buffer);

        $gfx->pushClip(new Rect(4, 4, 16, 16));
        $gfx->pushClip(new Rect(6, 6, 2, 2));
        $gfx->popClip();
        $gfx->fillRect(0, 0, self::SURFACE, self::SURFACE, self::COLOR);

        $this->assertSame([4, 4, 19, 19], $this->paintedBounds($buffer)?->toBounds());
    }

    /**
     * The clip is expressed in logical space, so a rotated renderer must map it
     * through the same rotation as the pixels it guards. At 90 degrees on a
     * square surface, logical (x, y) becomes physical (width - 1 - y, x), so the
     * logical 8..15 square lands at physical x 16..23, y 8..15.
     */
    public function testClippingComposesWithRotation(): void
    {
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $gfx = new PhpdafruitGfx($buffer);
        $gfx->rotation = 1;

        $gfx->pushClip(new Rect(8, 8, 8, 8));
        $gfx->fillRect(0, 0, self::SURFACE, self::SURFACE, self::COLOR);

        $this->assertSame([16, 8, 23, 15], $this->paintedBounds($buffer)?->toBounds());
    }

    public function testAFullyClippedFillTouchesNothingAndTransmitsNothing(): void
    {
        $buffer = new DirtyRegionsBuffer(self::SURFACE, self::SURFACE, $this->spec());
        $gfx = new PhpdafruitGfx($buffer);

        $gfx->pushClip(new Rect(0, 0, 4, 4));
        $gfx->pushClip(new Rect(20, 20, 4, 4));
        $gfx->fillRect(0, 0, self::SURFACE, self::SURFACE, self::COLOR);

        $this->assertNull($this->paintedBounds($buffer));
        $this->assertSame([], $buffer->dump());
    }

    /**
     * Clipping must stay analytic. The segment path is the documented fast path
     * for filled rectangles, so a clipped fill has to arrive as one narrowed
     * segment rather than as a per-pixel fallback.
     */
    public function testAClippedFillStaysASingleSegment(): void
    {
        $buffer = new RecordingFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $gfx = new PhpdafruitGfx($buffer);

        $gfx->pushClip($this->clipRegion());
        $gfx->fillRect(0, 0, self::SURFACE, self::SURFACE, self::COLOR);

        $this->assertSame([[8, 8, 16, 16, self::COLOR]], $buffer->segments);
    }

    public function testAnUnclippedFillStaysASingleFullSurfaceSegment(): void
    {
        $buffer = new RecordingFramebuffer(self::SURFACE, self::SURFACE, $this->spec());

        (new PhpdafruitGfx($buffer))->fillRect(0, 0, self::SURFACE, self::SURFACE, self::COLOR);

        $this->assertSame([[0, 0, 32, 32, self::COLOR]], $buffer->segments);
    }

    /**
     * The bounding box of every non-zero pixel, or null when nothing was painted.
     */
    protected function paintedBounds(Framebuffer $buffer): ?Rect
    {
        $left = PHP_INT_MAX;
        $top = PHP_INT_MAX;
        $right = -1;
        $bottom = -1;

        for ($y = 0; $y < self::SURFACE; $y++) {
            for ($x = 0; $x < self::SURFACE; $x++) {
                if ($buffer->getPixel($x, $y) === 0) {
                    continue;
                }

                $left = min($left, $x);
                $top = min($top, $y);
                $right = max($right, $x);
                $bottom = max($bottom, $y);
            }
        }

        return ($right < 0) ? null : Rect::fromBounds($left, $top, $right, $bottom);
    }

    /**
     * Large enough that outline primitives can straddle its edges rather than
     * enclosing it entirely.
     */
    protected function clipRegion(): Rect
    {
        return new Rect(8, 8, 16, 16);
    }

    protected function describe(Rect $rect): string
    {
        return implode(', ', $rect->toBounds());
    }

    protected function spec(): FormatSpec
    {
        return new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::MSB);
    }
}

/**
 * Records the segment writes it receives so tests can assert the renderer kept
 * using the bulk path instead of falling back to individual pixels.
 */
class RecordingFramebuffer extends FormatSpecFramebuffer
{
    /** @var array<int, array{0: int, 1: int, 2: int, 3: int, 4: int}> */
    public array $segments = [];

    public function setSegment(int $x, int $y, int $width, int $height, int $color): static
    {
        $this->segments[] = [$x, $y, $width, $height, $color];

        return parent::setSegment($x, $y, $width, $height, $color);
    }

    public function dump(): array
    {
        return [];
    }
}
