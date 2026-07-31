<?php

namespace DeptOfScrapyardRobotics\Tests\NutsAndBolts;

use Fabricate\NutsAndBolts\Geometry\Alignment;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AlignmentTest extends TestCase
{
    /**
     * A 20x10 child in a 100x50 container leaves 80x40 free, so the nine anchors
     * land on exact multiples with no rounding involved.
     *
     * @return array<string, array{0: Alignment, 1: int, 2: int}>
     */
    public static function anchors(): array
    {
        return [
            'top left' => [Alignment::topLeft(), 0, 0],
            'top center' => [Alignment::topCenter(), 40, 0],
            'top right' => [Alignment::topRight(), 80, 0],
            'center left' => [Alignment::centerLeft(), 0, 20],
            'center' => [Alignment::center(), 40, 20],
            'center right' => [Alignment::centerRight(), 80, 20],
            'bottom left' => [Alignment::bottomLeft(), 0, 40],
            'bottom center' => [Alignment::bottomCenter(), 40, 40],
            'bottom right' => [Alignment::bottomRight(), 80, 40],
        ];
    }

    #[DataProvider('anchors')]
    public function testEachAnchorPositionsTheChildExactly(Alignment $alignment, int $x, int $y): void
    {
        $placed = $alignment->positionIn(new Rect(0, 0, 100, 50), new Size(20, 10));

        $this->assertSame([$x, $y, 20, 10], [$placed->x, $placed->y, $placed->width, $placed->height]);
    }

    /**
     * The child is positioned relative to the container, not the surface, so a
     * container away from the origin carries its offset through.
     */
    public function testPlacementIsRelativeToTheContainerOrigin(): void
    {
        $placed = Alignment::center()->positionIn(new Rect(30, 15, 100, 50), new Size(20, 10));

        $this->assertSame([70, 35], [$placed->x, $placed->y]);
    }

    public function testTheChildSizeIsNeverChanged(): void
    {
        $placed = Alignment::bottomRight()->positionIn(new Rect(0, 0, 5, 5), new Size(20, 10));

        $this->assertSame([20, 10], [$placed->width, $placed->height], 'Alignment positions, it does not resize.');
    }

    /**
     * An oversized centred child must overhang evenly rather than being pinned
     * back to the origin, so the offset has to be allowed to go negative.
     */
    public function testAnOversizedChildOverhangsEvenly(): void
    {
        $placed = Alignment::center()->positionIn(new Rect(0, 0, 10, 10), new Size(20, 20));

        $this->assertSame([-5, -5], [$placed->x, $placed->y]);
        $this->assertSame(-5, $placed->x, 'Rounding must go away from zero, not toward it.');
    }

    /**
     * With an odd leftover the rounding is stable and does not drift toward the
     * origin: 7 free pixels centre at 4, not 3.
     */
    public function testOddLeftoverRoundsToNearest(): void
    {
        $placed = Alignment::center()->positionIn(new Rect(0, 0, 17, 17), new Size(10, 10));

        $this->assertSame([4, 4], [$placed->x, $placed->y]);
    }

    public function testAnExactFitLandsAtTheOriginForEveryAnchor(): void
    {
        foreach (static::anchors() as $label => [$alignment]) {
            $placed = $alignment->positionIn(new Rect(0, 0, 20, 10), new Size(20, 10));

            $this->assertSame([0, 0], [$placed->x, $placed->y], "{$label} with no free space");
        }
    }

    public function testArbitraryFractionsInterpolate(): void
    {
        $quarter = new Alignment(250, 750);
        $placed = $quarter->positionIn(new Rect(0, 0, 100, 100), new Size(20, 20));

        $this->assertSame([20, 60], [$placed->x, $placed->y]);
    }

    public function testEqualityComparesBothAxes(): void
    {
        $this->assertTrue(Alignment::center()->equals(new Alignment(500, 500)));
        $this->assertFalse(Alignment::center()->equals(Alignment::topCenter()));
    }
}
