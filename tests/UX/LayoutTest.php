<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\UX\Enums\CrossAxisAlignment;
use Fabricate\Contracts\UX\Enums\MainAxisAlignment;
use Fabricate\NutsAndBolts\Geometry\Alignment;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\EdgeInsets;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Layout\Align;
use Fabricate\UX\Layout\Column;
use Fabricate\UX\Layout\Expanded;
use Fabricate\UX\Layout\Padding;
use Fabricate\UX\Layout\Positioned;
use Fabricate\UX\Layout\Row;
use Fabricate\UX\Layout\Sized;
use Fabricate\UX\Layout\Stack;
use Fabricate\UX\Node;
use PHPUnit\Framework\TestCase;

/**
 * Geometry, asserted as numbers.
 *
 * Every case lays a subtree out against an explicit offer and then reads the
 * resulting bounds, because the point of retiring the sketches' hand-rolled
 * coordinate arithmetic is that the replacement can be checked exactly rather
 * than looked at.
 */
class LayoutTest extends TestCase
{
    /**
     * Criterion: a Row under a fixed offer places its children end to end and
     * fills the offer.
     */
    public function testARowPlacesChildrenEndToEnd(): void
    {
        $row = new Row;
        $row->add(
            new FilledNode(0, 0, 10, 4),
            new FilledNode(0, 0, 6, 8),
        );

        $size = $row->layout(Constraints::tight(new Size(40, 20)));

        $this->assertSame([40, 20], $this->extent($size));
        $this->assertSame([0, 0, 9, 3], $row->children()[0]->bounds()->toBounds());
        $this->assertSame([10, 0, 15, 7], $row->children()[1]->bounds()->toBounds());
    }

    public function testAColumnPlacesChildrenTopToBottom(): void
    {
        $column = new Column;
        $column->add(
            new FilledNode(0, 0, 10, 4),
            new FilledNode(0, 0, 6, 8),
        );

        $column->layout(Constraints::tight(new Size(40, 20)));

        $this->assertSame([0, 0, 9, 3], $column->children()[0]->bounds()->toBounds());
        $this->assertSame([0, 4, 5, 11], $column->children()[1]->bounds()->toBounds());
    }

    /**
     * With no ceiling there is nothing to fill, so the container collapses onto
     * its children instead of claiming PHP_INT_MAX.
     */
    public function testAnUnboundedRowShrinkWrapsItsChildren(): void
    {
        $row = new Row;
        $row->add(new FilledNode(0, 0, 10, 4), new FilledNode(0, 0, 6, 8));

        $size = $row->layout(Constraints::unbounded());

        $this->assertSame([16, 8], $this->extent($size));
    }

    public function testGapSeparatesChildrenAndCountsTowardsTheUsedExtent(): void
    {
        $row = (new Row)->gap(3);
        $row->add(new FilledNode(0, 0, 10, 4), new FilledNode(0, 0, 6, 4));

        $size = $row->layout(Constraints::unbounded());

        $this->assertSame([19, 4], $this->extent($size));
        $this->assertSame(13, $row->children()[1]->bounds()->x);
    }

    /**
     * Criterion: a flexible child takes the space the inflexible ones left, and
     * the split lands exactly on the container edge rather than a pixel short.
     */
    public function testExpandedChildrenSplitTheRemainderExactly(): void
    {
        $row = new Row;
        $fixed = new FilledNode(0, 0, 10, 4);
        $first = new Expanded(new FilledNode(0, 0, 0, 4), 1);
        $second = new Expanded(new FilledNode(0, 0, 0, 4), 2);
        $row->add($fixed, $first, $second);

        $row->layout(Constraints::tight(new Size(41, 10)));

        // 31 left over, split 1:2 — 10 and 21, which is what the running-total
        // share has to produce for the row to end flush at 41.
        $this->assertSame([0, 0, 9, 3], $fixed->bounds()->toBounds());
        $this->assertSame([10, 0, 19, 3], $first->bounds()->toBounds());
        $this->assertSame([20, 0, 40, 3], $second->bounds()->toBounds());
    }

    public function testAFlexibleChildPassesItsTightMainExtentToItsOwnChild(): void
    {
        $inner = new FilledNode(0, 0, 2, 4);
        $row = new Row;
        $row->add(new Expanded($inner));

        $row->layout(Constraints::tight(new Size(30, 10)));

        $this->assertSame(30, $inner->bounds()->width, 'Expanded must be transparent along the main axis.');
    }

    /**
     * With nothing to divide up, a flexible child is measured loosely and simply
     * takes the size it wants.
     */
    public function testAFlexibleChildShrinkWrapsWhenTheMainAxisIsUnbounded(): void
    {
        $row = new Row;
        $row->add(new Expanded(new FilledNode(0, 0, 7, 4)));

        $size = $row->layout(Constraints::unbounded());

        $this->assertSame([7, 4], $this->extent($size));
    }

    public function testAWeightOfZeroOptsBackOutOfTheSplit(): void
    {
        $row = new Row;
        $opted_out = new Expanded(new FilledNode(0, 0, 5, 4), 0);
        $row->add($opted_out);

        $row->layout(Constraints::tight(new Size(30, 10)));

        $this->assertSame(5, $opted_out->bounds()->width);
    }

    /**
     * @param  array{0: int, 1: int}  $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('mainAxisCases')]
    public function testMainAxisAlignmentDistributesTheLeftoverSpace(MainAxisAlignment $alignment, array $expected): void
    {
        $row = new Row($alignment);
        $first = new FilledNode(0, 0, 4, 4);
        $second = new FilledNode(0, 0, 4, 4);
        $row->add($first, $second);

        $row->layout(Constraints::tight(new Size(20, 4)));

        $this->assertSame($expected, [$first->bounds()->x, $second->bounds()->x]);
    }

    /**
     * Two 4px children in 20px leaves 12px free.
     *
     * @return array<string, array{0: MainAxisAlignment, 1: array{0: int, 1: int}}>
     */
    public static function mainAxisCases(): array
    {
        return [
            'start' => [MainAxisAlignment::START, [0, 4]],
            'center' => [MainAxisAlignment::CENTER, [6, 10]],
            'end' => [MainAxisAlignment::END, [12, 16]],
            'between' => [MainAxisAlignment::SPACE_BETWEEN, [0, 16]],
            'around' => [MainAxisAlignment::SPACE_AROUND, [3, 13]],
            'evenly' => [MainAxisAlignment::SPACE_EVENLY, [4, 12]],
        ];
    }

    /**
     * The lone-child case has its own arithmetic in each SPACE_ mode, because
     * "between" has no pair to sit between while the other two still owe the
     * child its outer margins.
     *
     * @param  array{0: int, 1: int}  $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('lonelyMainAxisCases')]
    public function testTheSpacingModesStillAgreeWithOneChild(MainAxisAlignment $alignment, int $expected): void
    {
        $row = new Row($alignment);
        $only = new FilledNode(0, 0, 4, 4);
        $row->add($only);

        $row->layout(Constraints::tight(new Size(20, 4)));

        $this->assertSame($expected, $only->bounds()->x);
    }

    /**
     * One 4px child in 20px leaves 16px free.
     *
     * @return array<string, array{0: MainAxisAlignment, 1: int}>
     */
    public static function lonelyMainAxisCases(): array
    {
        return [
            'between falls back to start' => [MainAxisAlignment::SPACE_BETWEEN, 0],
            'around gives half a share each side' => [MainAxisAlignment::SPACE_AROUND, 8],
            'evenly splits into two gaps' => [MainAxisAlignment::SPACE_EVENLY, 8],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('crossAxisCases')]
    public function testCrossAxisAlignmentPositionsOrStretches(CrossAxisAlignment $alignment, int $y, int $height): void
    {
        $row = new Row(cross_axis: $alignment);
        $child = new FilledNode(0, 0, 4, 6);
        $row->add($child);

        $row->layout(Constraints::tight(new Size(20, 16)));

        $this->assertSame([$y, $height], [$child->bounds()->y, $child->bounds()->height]);
    }

    /**
     * @return array<string, array{0: CrossAxisAlignment, 1: int, 2: int}>
     */
    public static function crossAxisCases(): array
    {
        return [
            'start' => [CrossAxisAlignment::START, 0, 6],
            'center' => [CrossAxisAlignment::CENTER, 5, 6],
            'end' => [CrossAxisAlignment::END, 10, 6],
            'stretch' => [CrossAxisAlignment::STRETCH, 0, 16],
        ];
    }

    /**
     * A hidden child does not merely measure to zero, it stops existing as far as
     * spacing is concerned — otherwise the gap it left would still be there.
     */
    public function testAHiddenChildTakesNoSpaceAndLeavesNoGap(): void
    {
        $row = (new Row)->gap(2);
        $first = new FilledNode(0, 0, 4, 4);
        $hidden = new FilledNode(0, 0, 4, 4);
        $last = new FilledNode(0, 0, 4, 4);
        $row->add($first, $hidden, $last);
        $hidden->hide();

        $size = $row->layout(Constraints::unbounded());

        $this->assertSame([10, 4], $this->extent($size));
        $this->assertSame(6, $last->bounds()->x);
    }

    /**
     * Measuring against a shrinking budget rather than an unbounded axis: on a
     * narrow panel the last child is squeezed instead of overflowing.
     */
    public function testChildrenAreMeasuredAgainstTheRoomThatIsLeft(): void
    {
        $row = new Row;
        $first = new FilledNode(0, 0, 12, 4);
        $second = new FilledNode(0, 0, 12, 4);
        $row->add($first, $second);

        $row->layout(Constraints::tight(new Size(16, 4)));

        $this->assertSame(12, $first->bounds()->width);
        $this->assertSame(4, $second->bounds()->width, 'The second child should have been clamped to what was left.');
    }

    public function testPaddingInsetsItsChildAndGrowsAroundIt(): void
    {
        $child = new FilledNode(0, 0, 10, 6);
        $padding = new Padding(new EdgeInsets(2, 3, 4, 5), $child);

        $size = $padding->layout(Constraints::unbounded());

        $this->assertSame([16, 14], $this->extent($size));
        $this->assertSame([2, 3, 11, 8], $child->bounds()->toBounds());
    }

    /**
     * Criterion: alignment and padding compose. The child is centred inside the
     * area padding left it, not inside the padded node's full box.
     */
    public function testPaddingAndAlignmentCompose(): void
    {
        $child = new FilledNode(0, 0, 4, 4);
        $padding = Padding::all(3, Align::centered($child));

        $padding->layout(Constraints::tight(new Size(20, 20)));

        // 20 minus 6 of padding leaves a 14px box; a 4px child centres at 5,
        // which is 3 of padding plus 5 into the inner box.
        $this->assertSame([8, 8, 11, 11], $child->globalBounds()->toBounds());
    }

    /**
     * Padding inside a tight offer still forces its child to fill what is left,
     * rather than letting it collapse onto its content.
     */
    public function testPaddingUnderATightOfferFillsTheRemainder(): void
    {
        $child = new FilledNode(0, 0, 1, 1);
        $padding = Padding::all(2, $child);

        $padding->layout(Constraints::tight(new Size(20, 12)));

        $this->assertSame([16, 8], [$child->bounds()->width, $child->bounds()->height]);
    }

    public function testAlignExpandsToFillAndPositionsItsChild(): void
    {
        $child = new FilledNode(0, 0, 6, 4);
        $align = new Align(Alignment::bottomRight(), $child);

        $size = $align->layout(Constraints::loose(new Size(20, 10)));

        $this->assertSame([20, 10], $this->extent($size), 'Alignment is meaningless in a collapsed box.');
        $this->assertSame([14, 6, 19, 9], $child->bounds()->toBounds());
    }

    public function testAlignShrinkWrapsAnUnboundedAxis(): void
    {
        $align = new Align(Alignment::center(), new FilledNode(0, 0, 6, 4));

        $size = $align->layout(new Constraints(0, 20, 0, PHP_INT_MAX));

        $this->assertSame([20, 4], $this->extent($size));
    }

    public function testSizedForcesItsExtentOnBothAxes(): void
    {
        $child = new FilledNode(0, 0, 2, 2);
        $sized = new Sized(12, 6, $child);

        $size = $sized->layout(Constraints::loose(new Size(40, 40)));

        $this->assertSame([12, 6], $this->extent($size));
        $this->assertSame([12, 6], [$child->bounds()->width, $child->bounds()->height]);
    }

    public function testSizedPassesAnUnfixedAxisThrough(): void
    {
        $child = new FilledNode(0, 0, 9, 5);
        $sized = Sized::width(12, $child);

        $size = $sized->layout(Constraints::loose(new Size(40, 40)));

        $this->assertSame([12, 5], $this->extent($size), 'The free axis should shrink-wrap the child.');
    }

    public function testSizedIsClampedByTheOfferItCannotExceed(): void
    {
        $sized = Sized::square(80, new FilledNode(0, 0, 2, 2));

        $size = $sized->layout(Constraints::loose(new Size(30, 20)));

        $this->assertSame([30, 20], $this->extent($size));
    }

    /**
     * Criterion: Stack z-order is child order, and every child shares the same
     * box.
     */
    public function testStackOverlaysChildrenInChildOrder(): void
    {
        $stack = new Stack;
        $under = new FilledNode(0, 0, 10, 10);
        $over = new FilledNode(0, 0, 6, 6);
        $stack->add($under, $over);

        $size = $stack->layout(Constraints::unbounded());

        $this->assertSame([10, 10], $this->extent($size), 'A stack takes the size of its largest child.');
        $this->assertSame([$under, $over], $stack->children(), 'Paint order is z-order, so it must be preserved.');
        $this->assertSame([0, 0], [$under->bounds()->x, $over->bounds()->x]);
    }

    public function testStackAlignsItsOrdinaryChildren(): void
    {
        $stack = new Stack(Alignment::center());
        $big = new FilledNode(0, 0, 10, 10);
        $small = new FilledNode(0, 0, 4, 4);
        $stack->add($big, $small);

        $stack->layout(Constraints::unbounded());

        $this->assertSame([3, 3, 6, 6], $small->bounds()->toBounds());
    }

    public function testPositionedPinsItselfBetweenTwoEdges(): void
    {
        $stack = new Stack;
        $stack->add(new FilledNode(0, 0, 40, 20));
        $pinned = new Positioned(left: 4, right: 6, bottom: 2, height: 5);
        $stack->add($pinned);

        $stack->layout(Constraints::unbounded());

        $this->assertSame([4, 13, 33, 17], $pinned->bounds()->toBounds());
    }

    public function testPositionedFillsTheStackWhenGivenEveryEdge(): void
    {
        $stack = new Stack;
        $stack->add(new FilledNode(0, 0, 30, 20));
        $child = new FilledNode(0, 0, 1, 1);
        $stack->add(Positioned::fill(3, $child));

        $stack->layout(Constraints::unbounded());

        $this->assertSame([3, 3, 26, 16], $child->globalBounds()->toBounds());
    }

    /**
     * A positioned child is placed against the stack, never allowed to inflate
     * it — otherwise pinning something to the bottom edge would move the edge.
     */
    public function testAPositionedChildCannotResizeTheStack(): void
    {
        $stack = new Stack;
        $stack->add(new FilledNode(0, 0, 10, 10));
        $stack->add(new Positioned(left: 0, top: 0, width: 100, height: 100));

        $size = $stack->layout(Constraints::unbounded());

        $this->assertSame([10, 10], $this->extent($size));
    }

    /**
     * Anchoring to the far edge alone sizes from the child and counts back from
     * that edge, which is the only path where the offset is computed rather
     * than given.
     */
    public function testPositionedAnchoredOnlyToTheFarEdgeCountsBackFromIt(): void
    {
        $stack = new Stack;
        $stack->add(new FilledNode(0, 0, 30, 20));
        $trailing = new Positioned(right: 2, bottom: 3, child: new FilledNode(0, 0, 8, 5));
        $stack->add($trailing);

        $stack->layout(Constraints::tight(new Size(30, 20)));

        $this->assertSame([20, 12, 27, 16], $trailing->bounds()->toBounds());
    }

    /**
     * Two insets that overlap would give a negative extent, which downstream
     * would become a Rect nobody can clip against. It collapses to nothing
     * instead.
     */
    public function testPositionedInsetsThatOverlapCollapseRatherThanGoNegative(): void
    {
        $stack = new Stack;
        $stack->add(new FilledNode(0, 0, 20, 20));
        $crushed = new Positioned(left: 14, top: 30, right: 12, bottom: 4);
        $stack->add($crushed);

        $stack->layout(Constraints::tight(new Size(20, 20)));

        $this->assertSame([0, 0], $this->extent($crushed->size()));
        $this->assertSame([14, 30], [$crushed->bounds()->x, $crushed->bounds()->y]);
    }

    public function testPositionedWithoutAnchorsShrinkWrapsAtTheOrigin(): void
    {
        $stack = new Stack;
        $stack->add(new FilledNode(0, 0, 20, 20));
        $loose = new Positioned(child: new FilledNode(0, 0, 5, 7));
        $stack->add($loose);

        $stack->layout(Constraints::unbounded());

        $this->assertSame([0, 0, 4, 6], $loose->bounds()->toBounds());
    }

    /**
     * The composition the sketch rewrites are built from: a column of rows, with
     * a flexible middle. Pinned as one set of numbers because the individual
     * nodes agreeing separately is not the same as them agreeing together.
     */
    public function testANestedTreeResolvesToExactCoordinates(): void
    {
        $header = new FilledNode(0, 0, 0, 8);
        $body = new FilledNode(0, 0, 0, 0);
        $footer = new FilledNode(0, 0, 0, 6);

        $column = new Column(cross_axis: CrossAxisAlignment::STRETCH);
        $column->add($header, new Expanded($body), $footer);

        $root = Padding::all(2, $column);
        $root->layout(Constraints::tight(new Size(128, 64)));

        $this->assertSame([2, 2, 125, 9], $header->globalBounds()->toBounds());
        $this->assertSame([2, 10, 125, 55], $body->globalBounds()->toBounds());
        $this->assertSame([2, 56, 125, 61], $footer->globalBounds()->toBounds());
    }

    /**
     * Criterion: a flex container claims the whole offered main axis by default,
     * which is what lets its main-axis alignment have room to distribute.
     */
    public function testAColumnFillsTheOfferedMainAxisByDefault(): void
    {
        $column = new Column;
        $column->add(new FilledNode(0, 0, 10, 8));

        $size = $column->layout(Constraints::loose(new Size(64, 64)));

        $this->assertSame([10, 64], $this->extent($size));
    }

    /**
     * The counterpart, and the reason the option exists: a card in a column has
     * to be exactly as tall as its contents, or it swallows the whole remaining
     * axis and squeezes every sibling after it to nothing.
     */
    public function testAColumnAskedToWrapIsExactlyAsTallAsItsChildren(): void
    {
        $column = (new Column(gap: 2))->wrapContent();
        $column->add(
            new FilledNode(0, 0, 10, 8),
            new FilledNode(0, 0, 10, 8),
        );

        $size = $column->layout(Constraints::loose(new Size(64, 64)));

        $this->assertSame([10, 18], $this->extent($size));
    }

    public function testWrappingCannotOverrideATightOffer(): void
    {
        $column = (new Column)->wrapContent();
        $column->add(new FilledNode(0, 0, 10, 8));

        $size = $column->layout(Constraints::tight(new Size(64, 64)));

        $this->assertSame([64, 64], $this->extent($size));
    }

    /**
     * A wrapping container next to a greedy one: the wrapped card keeps its own
     * height and the rows after it still get placed.
     */
    public function testAWrappedGroupLeavesRoomForItsSiblings(): void
    {
        $card = (new Column)->wrapContent();
        $card->add(new FilledNode(0, 0, 40, 20));

        $footer = new FilledNode(0, 0, 40, 8);

        $outer = new Column(main_axis: MainAxisAlignment::END);
        $outer->add($card, $footer);
        $outer->layout(Constraints::tight(new Size(64, 64)));

        $this->assertSame([0, 36, 39, 55], $card->globalBounds()->toBounds());
        $this->assertSame([0, 56, 39, 63], $footer->globalBounds()->toBounds());
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function extent(Size $size): array
    {
        return [$size->width, $size->height];
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function origin(Node $node): array
    {
        return [$node->bounds()->x, $node->bounds()->y];
    }
}
