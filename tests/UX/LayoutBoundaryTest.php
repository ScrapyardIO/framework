<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\UX\Enums\CrossAxisAlignment;
use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Layout\Column;
use Fabricate\UX\Layout\Expanded;
use Fabricate\UX\Layout\Row;
use Fabricate\UX\Layout\Sized;
use Fabricate\UX\Node;
use Fabricate\UX\Stage;
use PHPUnit\Framework\TestCase;

/**
 * What stops flex layout from costing a full tree walk per frame.
 *
 * The claim under test is narrow and mechanical: a change under a fixed-size
 * node reaches that node and no further, so the stage remeasures a subtree
 * rather than the tree. Everything else here exists to prove the boundary is
 * real rather than accidental — that a change *not* under one still climbs, and
 * that an unchanged frame does no layout at all.
 */
class LayoutBoundaryTest extends TestCase
{
    protected const int SURFACE = 64;

    public function testATightOfferMakesANodeALayoutBoundary(): void
    {
        $node = new FilledNode(0, 0, 4, 4);

        $this->assertFalse($node->isLayoutBoundary(), 'A node nobody has measured is not a boundary.');

        $node->layout(Constraints::loose(new Size(20, 20)));
        $this->assertFalse($node->isLayoutBoundary());

        $node->layout(Constraints::tight(new Size(10, 10)));
        $this->assertTrue($node->isLayoutBoundary());
    }

    /**
     * Criterion: a fixed-size subtree relayouts without marking its ancestors
     * dirty.
     */
    public function testAChangeInsideAFixedSizeSubtreeStopsAtTheBoundary(): void
    {
        [$stage, $root] = $this->stagedTree();
        $inner = new FilledNode(0, 0, 4, 4);
        $boundary = new Sized(20, 10, $inner);
        $root->add($boundary);
        $stage->render();

        $this->assertFalse($stage->needsLayout(), 'A settled stage should not want layout.');
        $this->assertTrue($inner->isLayoutBoundary(), 'Sized hands its child a tight offer.');

        $inner->resize(6, 6);

        $this->assertTrue($stage->needsLayout());
        $this->assertTrue($inner->needsLayout());
        $this->assertFalse($boundary->needsLayout(), 'The change escaped the fixed-size node.');
        $this->assertFalse($root->needsLayout(), 'The change reached the root.');
    }

    /**
     * The counterpart: without a boundary in the way, the same change has to
     * climb, or a Row would never redistribute when a child grew.
     */
    public function testAChangeWithNoBoundaryClimbsToTheRoot(): void
    {
        [$stage, $root] = $this->stagedTree();
        $child = new FilledNode(0, 0, 4, 4);
        $row = new Row;
        $row->add($child);
        $root->add($row);
        $stage->render();

        $child->resize(6, 6);

        $this->assertTrue($row->needsLayout());
        $this->assertTrue($root->needsLayout());
    }

    /**
     * Settling a boundary really does remeasure it, rather than only clearing
     * the flag.
     */
    public function testSettlingABoundaryRelaysOutTheSubtreeAndNothingAbove(): void
    {
        [$stage, $root] = $this->stagedTree();
        $first = new FilledNode(0, 0, 4, 4);
        $second = new FilledNode(0, 0, 4, 4);
        $inner = new Row(cross_axis: CrossAxisAlignment::START);
        $inner->add($first, $second);
        $root->add(new Sized(30, 10, $inner));
        $stage->render();

        $second->resize(9, 4);
        $stage->settleLayout();

        $this->assertFalse($stage->needsLayout());
        $this->assertSame([4, 0, 12, 3], $second->bounds()->toBounds(), 'The subtree was not relaid out.');
        $this->assertSame([0, 0, 29, 9], $inner->globalBounds()->toBounds(), 'The boundary absorbed the change.');
    }

    /**
     * Criterion: a frame that changed nothing does no layout, which is the same
     * gate the zero-paint idle frame relies on.
     */
    public function testAnUnchangedFrameDoesNoLayout(): void
    {
        [$stage, $root] = $this->stagedTree();
        $column = new Column;
        $column->add(new FilledNode(0, 0, 8, 8), new FilledNode(0, 0, 8, 8));
        $root->add($column);

        $this->assertTrue($stage->render());
        $this->assertFalse($stage->needsLayout());
        $this->assertFalse($column->needsLayout());
        $this->assertFalse($stage->render(), 'A settled tree must leave the frame idle.');
    }

    /**
     * The common case for a readout: the value changed, the size did not. That
     * has to be a repaint and nothing more, or every gauge tick would cost the
     * tree a remeasure.
     */
    public function testAContentChangeThatKeepsItsSizeCostsNoLayout(): void
    {
        [$stage, $root] = $this->stagedTree();
        $readout = new FilledNode(0, 0, 10, 10);
        $row = new Row;
        $row->add($readout);
        $root->add($row);
        $stage->render();

        $readout->touch();

        $this->assertTrue($stage->isDirty(), 'The change still has to repaint.');
        $this->assertFalse($stage->needsLayout(), 'A value change is not a size change.');
        $this->assertFalse($row->needsLayout());
    }

    /**
     * A repeated offer is answered from the memo, so an untouched subtree costs
     * nothing even when something above it is relaid out.
     */
    public function testAnUnchangedSubtreeIsNotRemeasured(): void
    {
        $column = new Column;
        $probe = new MeasureCountingNode(0, 0, 6, 6);
        $column->add($probe);

        $column->layout(Constraints::tight(new Size(20, 20)));
        $this->assertSame(1, $probe->measure_count);

        $column->layout(Constraints::tight(new Size(20, 20)), true);
        $this->assertSame(1, $probe->measure_count, 'The child answered the same offer twice.');
    }

    public function testAChangedSubtreeIsRemeasuredWhenItsParentSettles(): void
    {
        $column = new Column;
        $probe = new MeasureCountingNode(0, 0, 6, 6);
        $column->add($probe);
        $column->layout(Constraints::tight(new Size(20, 20)));

        $probe->markNeedsLayout();
        $column->layout(Constraints::tight(new Size(20, 20)), true);

        $this->assertSame(2, $probe->measure_count);
    }

    /**
     * The root is laid out tight to the surface, which is both how a tree learns
     * how much room it has and what stops relayout climbing off the top.
     */
    public function testTheRootIsMeasuredAgainstTheSurfaceExtent(): void
    {
        $stage = $this->stage();
        $root = new Row(cross_axis: CrossAxisAlignment::STRETCH);
        $child = new FilledNode(0, 0, 4, 4);
        $root->add(new Expanded($child));
        $stage->setRoot($root);

        $stage->render();

        $this->assertTrue($root->isLayoutBoundary());
        $this->assertSame([0, 0, 63, 63], $child->globalBounds()->toBounds());
    }

    /**
     * Layout runs before damage is collected, so the rects the stage reports are
     * where nodes ended up and not where they were about to be.
     */
    public function testDamageReflectsWhereLayoutPutThingsRatherThanWhereTheyWere(): void
    {
        $stage = $this->stage();
        $root = new Row;
        $first = new FilledNode(0, 0, 10, 10);
        $second = new FilledNode(0, 0, 10, 10);
        $root->add($first, $second);
        $stage->setRoot($root);
        $stage->render();

        $this->assertSame(10, $second->bounds()->x);

        $first->resize(20, 10);
        $stage->settleLayout();

        $this->assertSame(20, $second->bounds()->x, 'The sibling should have been pushed along.');

        // The grown node and the sibling's old and new positions all abut, so
        // they collapse into the single band the two of them now occupy.
        $this->assertEquals(
            [[0, 0, 29, 9]],
            array_map(static fn (Rect $rect): array => $rect->toBounds(), $stage->damageRegions()),
            'The area the sibling moved across must be damaged.',
        );
    }

    /**
     * A pass that settles on the geometry it already had must report nothing.
     *
     * Without this, any relayout would repaint everything it touched and the
     * boundary work above would buy nothing — the subtree would be smaller, but
     * it would still transmit.
     */
    public function testARelayoutThatMovesNothingReportsNoDamage(): void
    {
        [$stage, $root] = $this->stagedTree();
        $row = new Row;
        $row->add(new FilledNode(0, 0, 10, 10), new FilledNode(0, 0, 10, 10));
        $root->add($row);
        $stage->render();

        $row->markNeedsLayout();
        $stage->settleLayout();

        $this->assertSame([], $stage->damageRegions(), 'A settled pass damaged nodes that did not move.');
        $this->assertFalse($stage->isDirty());
    }

    /**
     * The join between the two halves: pixels land where layout said, with no
     * coordinate arithmetic anywhere in the tree that produced them.
     */
    public function testALaidOutTreePaintsWhereLayoutPutIt(): void
    {
        $buffer = new DirtyRegionsBuffer(self::SURFACE, self::SURFACE, UxHarness::spec());
        $stage = new Stage(UxHarness::presentation($buffer, self::SURFACE, self::SURFACE));

        $root = new Row(cross_axis: CrossAxisAlignment::STRETCH);
        $root->add(new Sized(16, null, new FilledNode(0, 0, 0, 0, 0)), new Expanded(new FilledNode(0, 0, 0, 0, 1)));
        $stage->setRoot($root);

        $stage->render();

        $this->assertSame(
            [16, 0, 63, 63],
            UxHarness::paintedBounds($buffer, self::SURFACE, self::SURFACE)?->toBounds(),
            'The lit half should start exactly where the fixed-width half ends.',
        );
    }

    /**
     * A boundary can be detached between the mark and the pass, and relayouting
     * an orphan would place children nothing is going to paint.
     */
    public function testABoundaryDetachedBeforeTheLayoutPassIsSkipped(): void
    {
        [$stage, $root] = $this->stagedTree();
        $inner = new FilledNode(0, 0, 4, 4);
        $boundary = new Sized(20, 10, $inner);
        $root->add($boundary);
        $stage->render();

        $inner->resize(6, 6);
        $root->remove($boundary);

        $stage->settleLayout();

        $this->assertFalse($stage->needsLayout());
    }

    /**
     * @return array{0: Stage, 1: Node}
     */
    protected function stagedTree(): array
    {
        $stage = $this->stage();
        $root = new FilledNode(0, 0, self::SURFACE, self::SURFACE);
        $stage->setRoot($root);

        return [$stage, $root];
    }

    protected function stage(): Stage
    {
        $buffer = new DirtyRegionsBuffer(self::SURFACE, self::SURFACE, UxHarness::spec());

        return new Stage(UxHarness::presentation($buffer, self::SURFACE, self::SURFACE));
    }
}
