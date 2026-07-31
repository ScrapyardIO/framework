<?php

namespace DeptOfScrapyardRobotics\Tests\NutsAndBolts;

use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\EdgeInsets;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Constraints are the entire layout protocol: a parent offers a range, a child
 * answers with a size inside it. These cases pin the parts layout leans on —
 * tightness (which is what makes a layout boundary), clamping, and deflation.
 */
class ConstraintsTest extends TestCase
{
    public function testTightConstraintsAllowExactlyOneSize(): void
    {
        $tight = Constraints::tight(new Size(40, 20));

        $this->assertTrue($tight->isTight());
        $this->assertTrue($tight->allows(new Size(40, 20)));
        $this->assertFalse($tight->allows(new Size(41, 20)));
        $this->assertTrue($tight->biggest()->equals($tight->smallest()));
    }

    public function testLooseConstraintsAllowAnythingUpToTheCeiling(): void
    {
        $loose = Constraints::loose(new Size(100, 50));

        $this->assertFalse($loose->isTight());
        $this->assertTrue($loose->allows(Size::zero()));
        $this->assertTrue($loose->allows(new Size(100, 50)));
        $this->assertFalse($loose->allows(new Size(101, 50)));
    }

    public function testConstrainClampsIntoRange(): void
    {
        $constraints = new Constraints(10, 100, 5, 50);

        $this->assertTrue($constraints->constrain(new Size(200, 200))->equals(new Size(100, 50)), 'clamps down');
        $this->assertTrue($constraints->constrain(new Size(1, 1))->equals(new Size(10, 5)), 'clamps up to the minimum');
        $this->assertTrue($constraints->constrain(new Size(40, 20))->equals(new Size(40, 20)), 'leaves valid sizes alone');
    }

    public function testUnboundedConstraintsReportThemselvesAsSuch(): void
    {
        $unbounded = Constraints::unbounded();

        $this->assertFalse($unbounded->hasBoundedWidth());
        $this->assertFalse($unbounded->hasBoundedHeight());
        $this->assertTrue(Constraints::loose(new Size(10, 10))->hasBoundedWidth());
    }

    public function testLooseningKeepsTheCeilingAndDropsTheFloor(): void
    {
        $loosened = (new Constraints(10, 100, 5, 50))->loosened();

        $this->assertSame([0, 100, 0, 50], [
            $loosened->min_width,
            $loosened->max_width,
            $loosened->min_height,
            $loosened->max_height,
        ]);
    }

    /**
     * A container that has spent space on padding passes down the remainder.
     */
    public function testDeflateReservesSpaceTheParentHasSpent(): void
    {
        $deflated = Constraints::loose(new Size(100, 50))->deflate(EdgeInsets::all(10));

        $this->assertSame([80, 30], [$deflated->max_width, $deflated->max_height]);
    }

    /**
     * Padding wider than the offer collapses the child rather than producing an
     * inverted range, which would throw from the constructor.
     */
    public function testDeflatingBeyondTheCeilingCollapsesToZero(): void
    {
        $deflated = Constraints::loose(new Size(10, 10))->deflate(EdgeInsets::all(20));

        $this->assertSame([0, 0], [$deflated->max_width, $deflated->max_height]);
        $this->assertTrue($deflated->biggest()->isEmpty());
    }

    public function testDeflatingAnUnboundedAxisStaysUnbounded(): void
    {
        $deflated = Constraints::unbounded()->deflate(EdgeInsets::all(10));

        $this->assertFalse($deflated->hasBoundedWidth());
        $this->assertFalse($deflated->hasBoundedHeight());
    }

    /**
     * Deflation must not leave the minimum above the new ceiling, or the result
     * would be an impossible range.
     */
    public function testDeflateLowersAMinimumThatNoLongerFits(): void
    {
        $deflated = (new Constraints(90, 100, 5, 50))->deflate(EdgeInsets::symmetric(20, 0));

        $this->assertSame(60, $deflated->max_width);
        $this->assertSame(60, $deflated->min_width, 'The floor must follow the ceiling down.');
        $this->assertTrue($deflated->allows($deflated->smallest()));
    }

    public function testItRejectsAnInvertedRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not exceed maximum');

        new Constraints(100, 10, 0, 10);
    }

    public function testItRejectsANegativeMinimum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Constraints(-1, 10, 0, 10);
    }

    public function testEqualityComparesTheWholeRange(): void
    {
        $this->assertTrue(Constraints::tight(new Size(4, 4))->equals(new Constraints(4, 4, 4, 4)));
        $this->assertFalse(Constraints::tight(new Size(4, 4))->equals(Constraints::loose(new Size(4, 4))));
    }

    /**
     * Tightness is what makes a node a layout boundary: its children cannot
     * change its size, so their resizing never reaches its ancestors.
     */
    public function testATightConstraintIsALayoutBoundary(): void
    {
        $fixed = Constraints::tight(new Size(32, 16));

        foreach ([new Size(0, 0), new Size(1000, 1000), new Size(32, 17)] as $child_answer) {
            $this->assertTrue(
                $fixed->constrain($child_answer)->equals(new Size(32, 16)),
                'No child answer may change a tightly constrained size.',
            );
        }
    }

    public function testBiggestAndSmallestAnchorAtTheOrigin(): void
    {
        $this->assertSame([0, 0, 99, 49], Constraints::loose(new Size(100, 50))->biggest()->atOrigin()->toBounds());
        $this->assertTrue(Constraints::loose(new Size(100, 50))->smallest()->equals(Size::zero()));
        $this->assertInstanceOf(Rect::class, Constraints::tight(new Size(2, 2))->biggest()->atOrigin());
    }
}
