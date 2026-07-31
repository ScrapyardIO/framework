<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\UX\Stage;
use PHPUnit\Framework\TestCase;

/**
 * The reactivity model is "a typed setter knows what changed", so these cases
 * pin two things: that a real change reports the right damage, and that a no-op
 * setter reports none. The second matters as much as the first — a setter that
 * always invalidates would repaint the tree every frame and throw away the whole
 * point of retained rendering.
 */
class NodeTest extends TestCase
{
    protected const int SURFACE = 64;

    public function testBoundsStartLocalAndReportSize(): void
    {
        $node = new FilledNode(3, 4, 10, 20);

        $this->assertSame([3, 4, 12, 23], $node->bounds()->toBounds());
        $this->assertSame([10, 20], [$node->size()->width, $node->size()->height]);
        $this->assertTrue($node->isVisible());
        $this->assertNull($node->parent());
        $this->assertSame([], $node->children());
    }

    public function testGlobalOriginAccumulatesThroughAncestors(): void
    {
        $root = new EmptyNode(1, 2, 60, 60);
        $middle = new EmptyNode(3, 4, 40, 40);
        $leaf = new FilledNode(5, 6, 10, 10);

        $root->add($middle);
        $middle->add($leaf);

        $this->assertSame([9, 12], [$leaf->globalOrigin()->x, $leaf->globalOrigin()->y]);
        $this->assertSame([9, 12, 18, 21], $leaf->globalBounds()->toBounds());
        $this->assertSame($root, $leaf->root());
    }

    public function testAddingSetsParentAndReparentingDetaches(): void
    {
        $first = new EmptyNode(0, 0, 10, 10);
        $second = new EmptyNode(0, 0, 10, 10);
        $child = new FilledNode(0, 0, 4, 4);

        $first->add($child);
        $this->assertSame($first, $child->parent());
        $this->assertSame([$child], $first->children());

        $second->add($child);
        $this->assertSame($second, $child->parent());
        $this->assertSame([], $first->children(), 'The child was left attached to both parents.');
        $this->assertSame([$child], $second->children());
    }

    public function testRemovingClearsTheParentAndReindexes(): void
    {
        $parent = new EmptyNode(0, 0, 10, 10);
        $first = new FilledNode(0, 0, 2, 2);
        $second = new FilledNode(2, 0, 2, 2);

        $parent->add($first, $second)->remove($first);

        $this->assertNull($first->parent());
        $this->assertSame([$second], $parent->children(), 'Children must stay a packed list.');
    }

    public function testRemovingANodeThatIsNotAChildIsANoOp(): void
    {
        $parent = new EmptyNode(0, 0, 10, 10);
        $stranger = new FilledNode(0, 0, 2, 2);

        $this->assertSame($parent, $parent->remove($stranger));
    }

    /**
     * A detached subtree has nowhere to report damage, and must not blow up for
     * it — trees get built before they are mounted.
     */
    public function testMutatingADetachedNodeDoesNotThrow(): void
    {
        $node = new FilledNode(0, 0, 4, 4);

        $this->assertNull($node->stage());
        $this->assertSame($node, $node->moveTo(5, 5));
        $this->assertSame($node, $node->resize(8, 8));
        $this->assertSame($node, $node->hide());
        $this->assertSame($node, $node->touch());
    }

    public function testMovingDamagesBothTheVacatedAndTheNewArea(): void
    {
        [$stage, $root] = $this->mountedStage();
        $child = new FilledNode(4, 4, 8, 8);
        $root->add($child);
        $stage->render();

        $child->moveTo(20, 20);

        $this->assertEquals(
            [[4, 4, 11, 11], [20, 20, 27, 27]],
            $this->sortedRegions($stage),
            'A move must damage where the node was as well as where it went.',
        );
    }

    public function testResizingDamagesTheOldAndNewExtent(): void
    {
        [$stage, $root] = $this->mountedStage();
        $child = new FilledNode(4, 4, 8, 8);
        $root->add($child);
        $stage->render();

        $child->resize(4, 4);

        // The shrunken extent is inside the old one, so they coalesce into the
        // larger of the two rather than staying separate.
        $this->assertEquals([[4, 4, 11, 11]], $this->sortedRegions($stage));
    }

    /**
     * Criterion: setting a property to the value it already holds invalidates
     * nothing at all.
     */
    public function testSettingAnUnchangedValueInvalidatesNothing(): void
    {
        [$stage, $root] = $this->mountedStage();
        $child = new FilledNode(4, 4, 8, 8);
        $root->add($child);
        $stage->render();

        $this->assertFalse($stage->isDirty(), 'The stage should be clean after a render.');

        $child->moveTo(4, 4);
        $child->resize(8, 8);
        $child->setBounds(new Rect(4, 4, 8, 8));
        $child->show();
        $child->setVisible(true);

        $this->assertFalse($stage->isDirty(), 'A no-op setter reported damage.');
        $this->assertSame([], $stage->damageRegions());
    }

    public function testHidingAndShowingDamageTheOccupiedArea(): void
    {
        [$stage, $root] = $this->mountedStage();
        $child = new FilledNode(4, 4, 8, 8);
        $root->add($child);
        $stage->render();

        $child->hide();
        $this->assertEquals([[4, 4, 11, 11]], $this->sortedRegions($stage));

        $stage->render();
        $child->show();
        $this->assertEquals([[4, 4, 11, 11]], $this->sortedRegions($stage));
    }

    public function testAHiddenNodeAndItsChildrenAreNotPainted(): void
    {
        [$stage, $root] = $this->mountedStage();
        $parent = new FilledNode(4, 4, 16, 16);
        $child = new FilledNode(0, 0, 4, 4);
        $parent->add($child);
        $root->add($parent);

        $parent->hide();
        $stage->render();

        $this->assertSame(0, $parent->paint_count);
        $this->assertSame(0, $child->paint_count, 'Children of a hidden node must not paint.');
    }

    public function testAddingAChildDamagesItsArea(): void
    {
        [$stage, $root] = $this->mountedStage();
        $stage->render();

        $root->add(new FilledNode(10, 10, 6, 6));

        $this->assertEquals([[10, 10, 15, 15]], $this->sortedRegions($stage));
    }

    /**
     * Removal has to report the vacated area while the child can still say where
     * it was — afterwards it has no parent to compute a global position from.
     */
    public function testRemovingAChildDamagesTheAreaItVacated(): void
    {
        [$stage, $root] = $this->mountedStage();
        $child = new FilledNode(10, 10, 6, 6);
        $root->add($child);
        $stage->render();

        $root->remove($child);

        $this->assertEquals([[10, 10, 15, 15]], $this->sortedRegions($stage));
    }

    /**
     * @return array{0: Stage, 1: FilledNode}
     */
    protected function mountedStage(): array
    {
        $buffer = new DirtyRegionsBuffer(self::SURFACE, self::SURFACE, UxHarness::spec());
        $stage = new Stage(UxHarness::presentation($buffer, self::SURFACE, self::SURFACE));
        $root = new FilledNode(0, 0, self::SURFACE, self::SURFACE);
        $stage->setRoot($root);

        return [$stage, $root];
    }

    /**
     * @return array<int, array{0: int, 1: int, 2: int, 3: int}>
     */
    protected function sortedRegions(Stage $stage): array
    {
        $bounds = array_map(fn (Rect $rect): array => $rect->toBounds(), $stage->damageRegions());

        sort($bounds);

        return $bounds;
    }
}
