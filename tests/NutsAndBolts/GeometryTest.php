<?php

namespace DeptOfScrapyardRobotics\Tests\NutsAndBolts;

use Fabricate\NutsAndBolts\Geometry\EdgeInsets;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;
use PHPUnit\Framework\TestCase;

/**
 * Rect has its own suite; these are the companions layout needs so it can talk
 * about a position or an extent before it knows the other half.
 */
class GeometryTest extends TestCase
{
    public function testAPointTranslates(): void
    {
        $moved = (new Point(3, 4))->translate(-5, 6);

        $this->assertSame([-2, 10], [$moved->x, $moved->y]);
        $this->assertTrue(Point::origin()->equals(new Point(0, 0)));
        $this->assertFalse((new Point(1, 0))->equals(new Point(0, 1)));
    }

    public function testAPointPlusASizeIsARect(): void
    {
        $rect = (new Point(4, 6))->withSize(new Size(10, 20));

        $this->assertSame([4, 6, 13, 25], $rect->toBounds());
    }

    public function testASizeReportsEmptinessLikeARect(): void
    {
        $this->assertTrue(Size::zero()->isEmpty());
        $this->assertTrue((new Size(0, 10))->isEmpty());
        $this->assertTrue((new Size(10, -1))->isEmpty());
        $this->assertFalse((new Size(1, 1))->isEmpty());
        $this->assertSame(0, Size::zero()->area());
        $this->assertSame(200, (new Size(10, 20))->area());
    }

    /**
     * A collapsed size is a collapsed size regardless of which axis collapsed,
     * matching Rect::equals() treating all empty rects as equal.
     */
    public function testEmptySizesAreEqualWhicheverAxisCollapsed(): void
    {
        $this->assertTrue((new Size(0, 5))->equals(new Size(7, 0)));
        $this->assertTrue((new Size(4, 4))->equals(Size::square(4)));
        $this->assertFalse((new Size(4, 5))->equals(new Size(5, 4)));
    }

    public function testASizeConstrainsWithoutGrowing(): void
    {
        $constrained = (new Size(100, 10))->constrainTo(new Size(50, 50));

        $this->assertSame([50, 10], [$constrained->width, $constrained->height]);
    }

    public function testASizeAtOriginIsARect(): void
    {
        $this->assertSame([0, 0, 9, 19], (new Size(10, 20))->atOrigin()->toBounds());
    }

    public function testInsetsSumPerAxis(): void
    {
        $insets = new EdgeInsets(1, 2, 3, 4);

        $this->assertSame(4, $insets->horizontal());
        $this->assertSame(6, $insets->vertical());
        $this->assertTrue(EdgeInsets::zero()->isZero());
        $this->assertFalse($insets->isZero());
    }

    public function testSymmetricAndUniformInsets(): void
    {
        $this->assertTrue(EdgeInsets::all(3)->equals(new EdgeInsets(3, 3, 3, 3)));
        $this->assertTrue(EdgeInsets::symmetric(2, 5)->equals(new EdgeInsets(2, 5, 2, 5)));
    }

    public function testDeflateShrinksInwards(): void
    {
        $deflated = EdgeInsets::all(2)->deflate(new Rect(0, 0, 20, 20));

        $this->assertSame([2, 2, 17, 17], $deflated->toBounds());
    }

    /**
     * Over-deflating collapses rather than inverting, so callers branch on
     * isEmpty() instead of guarding against a negative extent.
     */
    public function testOverDeflatingCollapsesToEmpty(): void
    {
        $this->assertTrue(EdgeInsets::all(10)->deflate(new Rect(0, 0, 8, 8))->isEmpty());
        $this->assertTrue(EdgeInsets::all(4)->deflate(new Rect(0, 0, 8, 8))->isEmpty(), 'exactly consumed is empty too');
    }

    public function testInflateIsTheInverseOfDeflate(): void
    {
        $original = new Rect(5, 5, 20, 20);
        $insets = new EdgeInsets(1, 2, 3, 4);

        $this->assertTrue($insets->inflate($insets->deflate($original))->equals($original));
    }

    /**
     * Negative insets grow, which is what lets one insets value describe both
     * padding inside a node and an outline drawn outside it.
     */
    public function testNegativeInsetsGrow(): void
    {
        $grown = EdgeInsets::all(-2)->deflate(new Rect(4, 4, 10, 10));

        $this->assertSame([2, 2, 15, 15], $grown->toBounds());
    }
}
