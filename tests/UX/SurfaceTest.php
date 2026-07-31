<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\Endianness;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\Framebuffers\Strategy\FullFramebuffer;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Surface;
use Microscrap\GFX\PhpdaFruit\PhpdafruitGfx;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Surface makes two promises, and both have to hold for every primitive rather
 * than for the handful that are easy to reason about: local coordinates land at
 * the right global position, and a node cannot paint outside its own bounds.
 *
 * Translation is done per-method, so the risk is a *forgotten* method — hence a
 * provider that drives all of them rather than a sample.
 */
class SurfaceTest extends TestCase
{
    protected const int SURFACE = 32;

    protected const int COLOR = 0xFFFF;

    /**
     * The node's box inside the 32x32 surface. Deliberately offset on both axes
     * and away from every edge, so a missing translation cannot coincidentally
     * land in the right place.
     */
    protected function nodeBox(): Rect
    {
        return new Rect(10, 6, 12, 14);
    }

    /**
     * Every DrawingSurface method, each overdrawing the node box on all sides in
     * *local* coordinates.
     *
     * @return array<string, array{0: callable(Surface): void}>
     */
    public static function overdrawingPrimitives(): array
    {
        $color = self::COLOR;
        $over = self::SURFACE;

        return [
            'drawPixel' => [fn (Surface $s) => $s
                ->drawPixel(-4, -4, $color)
                ->drawPixel(2, 2, $color)
                ->drawPixel($over, $over, $color)],
            'drawPixels' => [fn (Surface $s) => $s->drawPixels([
                [-2, -2, $color], [3, 3, $color], [$over, $over, $color],
            ])],
            'drawLine' => [fn (Surface $s) => $s->drawLine(-$over, -$over, $over, $over, $color)],
            'drawLines' => [fn (Surface $s) => $s->drawLines([
                [-$over, -$over, $over, $over, $color],
                [-$over, $over, $over, -$over, $color],
            ])],
            'drawHorizontalLine' => [fn (Surface $s) => $s->drawHorizontalLine(-$over, 4, $over * 3, $color)],
            'drawVerticalLine' => [fn (Surface $s) => $s->drawVerticalLine(4, -$over, $over * 3, $color)],
            'drawRect' => [fn (Surface $s) => $s->drawRect(-2, -2, 14, 16, $color)],
            'fillRect' => [fn (Surface $s) => $s->fillRect(-$over, -$over, $over * 3, $over * 3, $color)],
            'drawRoundRect' => [fn (Surface $s) => $s->drawRoundRect(-2, -2, 14, 16, 4, $color)],
            'fillRoundRect' => [fn (Surface $s) => $s->fillRoundRect(-4, -4, 20, 22, 4, $color)],
            // Radii are sized so the outline crosses the box edges. A larger
            // shape would enclose the 12x14 box entirely and paint nothing
            // inside it, proving nothing.
            'drawCircle' => [fn (Surface $s) => $s->drawCircle(4, 4, 6, $color)],
            'fillCircle' => [fn (Surface $s) => $s->fillCircle(4, 4, 20, $color)],
            'drawEllipse' => [fn (Surface $s) => $s->drawEllipse(4, 4, 8, 6, $color)],
            'fillEllipse' => [fn (Surface $s) => $s->fillEllipse(4, 4, 20, 18, $color)],
            'drawTriangle' => [fn (Surface $s) => $s->drawTriangle(-6, 20, 6, -6, 20, 20, $color)],
            'fillTriangle' => [fn (Surface $s) => $s->fillTriangle(-6, 20, 6, -6, 20, 20, $color)],
            'fill' => [fn (Surface $s) => $s->fill($color)],
            'drawChar' => [fn (Surface $s) => $s->drawChar(-2, -2, ord('M'), $color, 0, 4, 4)],
            'text' => [fn (Surface $s) => $s
                ->setTextColor($color)
                ->setTextSize(2)
                ->setCursor(-2, 2)
                ->print('MMMMMMMM')],
        ];
    }

    /**
     * @param  callable(Surface): void  $paint
     */
    #[DataProvider('overdrawingPrimitives')]
    public function testNoPrimitiveEscapesTheNodeBounds(callable $paint): void
    {
        $box = $this->nodeBox();
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $surface = $this->rootSurface($buffer)->forChild($box);

        $surface->paint(fn () => $paint($surface));

        $painted = $this->paintedBounds($buffer);

        $this->assertNotNull($painted, 'The primitive painted nothing, so the assertion would pass vacuously.');
        $this->assertTrue(
            $box->containsRect($painted),
            "Painted [{$this->describe($painted)}] escapes the node box [{$this->describe($box)}].",
        );
    }

    /**
     * The dirty-tracking half of the same promise: escaping the bounds costs a
     * spurious transmit even when the pixel itself is invisible.
     *
     * @param  callable(Surface): void  $paint
     */
    #[DataProvider('overdrawingPrimitives')]
    public function testNoPrimitiveMarksDirtyOutsideTheNodeBounds(callable $paint): void
    {
        $box = $this->nodeBox();
        $buffer = new DirtyRegionsBuffer(self::SURFACE, self::SURFACE, $this->spec());
        $surface = $this->rootSurface($buffer)->forChild($box);

        $surface->paint(fn () => $paint($surface));

        $updates = $buffer->dump();

        $this->assertNotSame([], $updates, 'Nothing was marked dirty, so the assertion would pass vacuously.');

        foreach ($updates as $update) {
            $dirty = new Rect($update->origin_x, $update->origin_y, $update->width, $update->height);

            $this->assertTrue(
                $box->containsRect($dirty),
                "Dirty region [{$this->describe($dirty)}] escapes the node box [{$this->describe($box)}].",
            );
        }
    }

    /**
     * The positive half: local 0,0 must actually be the node's top-left, not the
     * screen's. A surface that silently dropped the offset would still satisfy
     * the containment tests above by painting in the wrong place.
     */
    public function testLocalOriginMapsToTheNodeOrigin(): void
    {
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $surface = $this->rootSurface($buffer)->forChild(new Rect(10, 6, 12, 14));

        $surface->paint(fn () => $surface->fillRect(0, 0, 4, 3, self::COLOR));

        $this->assertSame([10, 6, 13, 8], $this->paintedBounds($buffer)?->toBounds());
    }

    public function testFillCoversTheWholeNodeAndNothingMore(): void
    {
        $box = $this->nodeBox();
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $surface = $this->rootSurface($buffer)->forChild($box);

        $surface->paint(fn () => $surface->fill(self::COLOR));

        $this->assertSame($box->toBounds(), $this->paintedBounds($buffer)?->toBounds());
    }

    /**
     * A child's offsets accumulate through every ancestor, so a grandchild sits
     * at the sum of the chain rather than at its own local position.
     */
    public function testNestedSurfacesAccumulateTranslation(): void
    {
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $grandchild = $this->rootSurface($buffer)
            ->forChild(new Rect(4, 4, 24, 24))
            ->forChild(new Rect(3, 2, 10, 10));

        $this->assertSame([7, 6], [$grandchild->origin()->x, $grandchild->origin()->y]);

        $grandchild->paint(fn () => $grandchild->fillRect(0, 0, 2, 2, self::COLOR));

        $this->assertSame([7, 6, 8, 7], $this->paintedBounds($buffer)?->toBounds());
    }

    /**
     * A child can only ever narrow the paintable area. A child positioned partly
     * outside its parent is confined to the overlap, so no depth of nesting lets
     * a node escape an ancestor.
     */
    public function testAChildCannotEscapeItsParentBounds(): void
    {
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $parent = $this->rootSurface($buffer)->forChild(new Rect(8, 8, 10, 10));
        $child = $parent->forChild(new Rect(5, 5, 20, 20));

        $this->assertSame([13, 13, 17, 17], $child->clip()->toBounds());

        $child->paint(fn () => $child->fill(self::COLOR));

        $this->assertSame([13, 13, 17, 17], $this->paintedBounds($buffer)?->toBounds());
    }

    public function testAChildEntirelyOutsideItsParentIsFullyClipped(): void
    {
        $parent = $this->rootSurface(new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec()))
            ->forChild(new Rect(0, 0, 8, 8));

        $this->assertTrue($parent->forChild(new Rect(20, 20, 4, 4))->isFullyClipped());
        $this->assertFalse($parent->forChild(new Rect(4, 4, 4, 4))->isFullyClipped());
    }

    /**
     * paint() must restore the previous clip even when the painter throws, or one
     * failing node would silently confine every node painted after it.
     */
    public function testPaintRestoresTheClipAfterAThrow(): void
    {
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $gfx = new PhpdafruitGfx($buffer);
        $surface = Surface::root($gfx, self::SURFACE, self::SURFACE)->forChild(new Rect(2, 2, 4, 4));

        try {
            $surface->paint(function (): void {
                throw new \RuntimeException('node blew up mid-paint');
            });
        } catch (\RuntimeException) {
            // Expected: the point is what the clip stack looks like afterwards.
        }

        $this->assertNull($gfx->clip(), 'A throwing painter left its clip on the stack.');
    }

    /**
     * getTextBounds takes local coordinates and must hand local coordinates back,
     * or a node would measure text in one space and lay it out in another. This is
     * the concrete payoff of translating at the API boundary instead of down in
     * the pixel funnel.
     */
    public function testTextBoundsAreReportedInLocalCoordinates(): void
    {
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $gfx = new PhpdafruitGfx($buffer);
        $box = new Rect(10, 6, 20, 20);
        $surface = Surface::root($gfx, self::SURFACE, self::SURFACE)->forChild($box);

        $global = $gfx->getTextBounds('Hi', 10 + 2, 6 + 3);
        $local = $surface->getTextBounds('Hi', 2, 3);

        $this->assertSame($global['w'], $local['w']);
        $this->assertSame($global['h'], $local['h']);
        $this->assertSame($global['x1'] - 10, $local['x1']);
        $this->assertSame($global['y1'] - 6, $local['y1']);
    }

    public function testItReportsItsOwnExtentInLocalTerms(): void
    {
        $surface = $this->rootSurface(new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec()))
            ->forChild(new Rect(10, 6, 12, 14));

        $this->assertTrue($surface->size()->equals(new Size(12, 14)));
        $this->assertSame([0, 0, 11, 13], $surface->bounds()->toBounds());
        $this->assertSame([12, 9], [$surface->toGlobal(2, 3)->x, $surface->toGlobal(2, 3)->y]);
    }

    /**
     * Translation happens in logical space and the driver rotates afterwards, so
     * a rotated renderer must still confine a node to its own box — just at the
     * rotated physical position. At 90 degrees on a square surface logical (x, y)
     * becomes physical (width - 1 - y, x).
     */
    public function testTranslationComposesWithRotation(): void
    {
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, $this->spec());
        $gfx = new PhpdafruitGfx($buffer);
        $gfx->rotation = 1;

        $surface = Surface::root($gfx, self::SURFACE, self::SURFACE)->forChild(new Rect(10, 6, 12, 14));

        $surface->paint(fn () => $surface->fill(self::COLOR));

        // Logical x 10..21, y 6..19 maps to physical x 12..25, y 10..21.
        $this->assertSame([12, 10, 25, 21], $this->paintedBounds($buffer)?->toBounds());
    }

    protected function rootSurface(Framebuffer $buffer): Surface
    {
        return Surface::root(new PhpdafruitGfx($buffer), self::SURFACE, self::SURFACE);
    }

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

    protected function describe(Rect $rect): string
    {
        return implode(', ', $rect->toBounds());
    }

    protected function spec(): FormatSpec
    {
        return new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::MSB);
    }
}
