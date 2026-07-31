<?php

namespace DeptOfScrapyardRobotics\Tests\NutsAndBolts;

use Fabricate\NutsAndBolts\Geometry\Rect;
use PHPUnit\Framework\TestCase;

class RectTest extends TestCase
{
    public function testItTreatsBoundsAsInclusive(): void
    {
        $rect = new Rect(4, 6, 10, 20);

        $this->assertSame(4, $rect->left());
        $this->assertSame(6, $rect->top());
        $this->assertSame(13, $rect->right());
        $this->assertSame(25, $rect->bottom());
    }

    public function testASinglePixelRectHasEqualEdges(): void
    {
        $rect = new Rect(5, 9, 1, 1);

        $this->assertSame(5, $rect->right());
        $this->assertSame(9, $rect->bottom());
        $this->assertSame(1, $rect->area());
    }

    public function testFromBoundsRoundTripsThroughToBounds(): void
    {
        $rect = Rect::fromBounds(3, 7, 12, 19);

        $this->assertSame(10, $rect->width);
        $this->assertSame(13, $rect->height);
        $this->assertSame([3, 7, 12, 19], $rect->toBounds());
    }

    public function testNonPositiveDimensionsAreEmpty(): void
    {
        $this->assertTrue((new Rect(0, 0, 0, 4))->isEmpty());
        $this->assertTrue((new Rect(0, 0, 4, 0))->isEmpty());
        $this->assertTrue((new Rect(2, 2, -3, 5))->isEmpty());
        $this->assertTrue(Rect::empty()->isEmpty());
        $this->assertFalse((new Rect(0, 0, 1, 1))->isEmpty());
    }

    public function testEmptyRectsHaveNoAreaAndContainNothing(): void
    {
        $empty = Rect::empty();

        $this->assertSame(0, $empty->area());
        $this->assertFalse($empty->contains(0, 0));
        $this->assertFalse($empty->intersects(new Rect(0, 0, 4, 4)));
        $this->assertFalse($empty->touches(new Rect(0, 0, 4, 4)));
    }

    public function testContainsIncludesEveryEdge(): void
    {
        $rect = new Rect(10, 10, 5, 5);

        $this->assertTrue($rect->contains(10, 10));
        $this->assertTrue($rect->contains(14, 14));
        $this->assertTrue($rect->contains(12, 12));
        $this->assertFalse($rect->contains(9, 12));
        $this->assertFalse($rect->contains(15, 12));
        $this->assertFalse($rect->contains(12, 9));
        $this->assertFalse($rect->contains(12, 15));
    }

    public function testIntersectClampsToTheOverlap(): void
    {
        $intersection = (new Rect(0, 0, 20, 20))->intersect(new Rect(10, 10, 20, 20));

        $this->assertSame([10, 10, 19, 19], $intersection->toBounds());
    }

    public function testIntersectYieldsAnEmptyRectWhenDisjoint(): void
    {
        $intersection = (new Rect(0, 0, 5, 5))->intersect(new Rect(20, 20, 5, 5));

        $this->assertTrue($intersection->isEmpty());
    }

    public function testAdjacentRectsDoNotIntersectButDoTouch(): void
    {
        $left = new Rect(0, 0, 10, 10);
        $right = new Rect(10, 0, 10, 10);

        $this->assertFalse($left->intersects($right));
        $this->assertTrue($left->touches($right));
        $this->assertTrue($left->intersect($right)->isEmpty());
    }

    /**
     * Only rects sharing an edge coalesce. One empty column between them is
     * already a gap, matching the adjacency test the dirty-region merge uses.
     */
    public function testRectsSeparatedByAGapDoNotTouch(): void
    {
        $left = new Rect(0, 0, 10, 10);

        $this->assertTrue($left->touches(new Rect(10, 0, 10, 10)));
        $this->assertFalse($left->touches(new Rect(11, 0, 10, 10)));
        $this->assertFalse($left->touches(new Rect(20, 0, 10, 10)));
    }

    public function testTouchingIsSymmetric(): void
    {
        $left = new Rect(0, 0, 10, 10);
        $adjacent = new Rect(10, 0, 10, 10);

        $this->assertSame($left->touches($adjacent), $adjacent->touches($left));
    }

    public function testUnionCoversBoth(): void
    {
        $union = (new Rect(2, 3, 4, 4))->union(new Rect(20, 25, 2, 2));

        $this->assertSame([2, 3, 21, 26], $union->toBounds());
    }

    public function testUnionIgnoresAnEmptyOperandInsteadOfPullingToTheOrigin(): void
    {
        $rect = new Rect(20, 20, 5, 5);

        $this->assertSame($rect->toBounds(), $rect->union(Rect::empty())->toBounds());
        $this->assertSame($rect->toBounds(), Rect::empty()->union($rect)->toBounds());
    }

    public function testContainsRectRequiresFullEnclosure(): void
    {
        $outer = new Rect(0, 0, 20, 20);

        $this->assertTrue($outer->containsRect(new Rect(0, 0, 20, 20)));
        $this->assertTrue($outer->containsRect(new Rect(5, 5, 5, 5)));
        $this->assertFalse($outer->containsRect(new Rect(15, 15, 10, 10)));
        $this->assertFalse($outer->containsRect(Rect::empty()));
    }

    public function testTranslateMovesTheOriginAndKeepsTheExtent(): void
    {
        $moved = (new Rect(5, 5, 10, 4))->translate(-3, 7);

        $this->assertSame([2, 12, 11, 15], $moved->toBounds());
    }

    public function testEqualsComparesGeometryAndTreatsAllEmptiesAsEqual(): void
    {
        $this->assertTrue((new Rect(1, 2, 3, 4))->equals(new Rect(1, 2, 3, 4)));
        $this->assertFalse((new Rect(1, 2, 3, 4))->equals(new Rect(1, 2, 3, 5)));
        $this->assertTrue((new Rect(9, 9, 0, 0))->equals(Rect::empty()));
    }
}
