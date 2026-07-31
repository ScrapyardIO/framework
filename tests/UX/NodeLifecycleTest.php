<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Stage;
use PHPUnit\Framework\TestCase;

/**
 * mount() and measure(), the two halves of the node lifecycle that layout in the
 * next slice builds on.
 */
class NodeLifecycleTest extends TestCase
{
    public function testMountingHappensWhenTheTreeJoinsAStage(): void
    {
        $root = new EmptyNode(0, 0, 32, 32);
        $child = new EmptyNode(0, 0, 8, 8);
        $root->add($child);

        $this->assertFalse($root->isMounted(), 'A detached tree is not mounted.');
        $this->assertSame(0, $child->mount_count);

        $this->stage()->setRoot($root);

        $this->assertTrue($root->isMounted());
        $this->assertSame(1, $root->mount_count);
        $this->assertSame(1, $child->mount_count, 'Mounting must reach the whole subtree.');
    }

    /**
     * Mounting is a one-time hook, so a node that is moved around the tree must not
     * run it again — a second call would re-allocate whatever it set up.
     */
    public function testMountingHappensOnlyOnce(): void
    {
        $root = new EmptyNode(0, 0, 32, 32);
        $child = new EmptyNode(0, 0, 8, 8);
        $stage = $this->stage();
        $stage->setRoot($root);
        $root->add($child);

        $this->assertSame(1, $child->mount_count);

        $sibling = new EmptyNode(0, 0, 4, 4);
        $root->add($sibling);
        $child->detach();
        $sibling->add($child);

        $this->assertSame(1, $child->mount_count, 'Re-parenting must not re-mount.');
    }

    /**
     * A node added to an already-staged tree mounts immediately rather than waiting
     * for a frame.
     */
    public function testAddingToAStagedTreeMountsTheNewChild(): void
    {
        $root = new EmptyNode(0, 0, 32, 32);
        $this->stage()->setRoot($root);

        $late = new EmptyNode(0, 0, 4, 4);

        $this->assertSame(0, $late->mount_count);

        $root->add($late);

        $this->assertSame(1, $late->mount_count);
    }

    public function testAddingToADetachedTreeDoesNotMount(): void
    {
        $root = new EmptyNode(0, 0, 32, 32);
        $child = new EmptyNode(0, 0, 4, 4);

        $root->add($child);

        $this->assertSame(0, $child->mount_count, 'Nothing mounts until the tree reaches a stage.');
    }

    /**
     * The default measure respects the size the node was given, clamped to what the
     * parent will accept. Layout nodes override it; a plain node should not have to.
     */
    public function testTheDefaultMeasureRespectsTheNodesOwnSize(): void
    {
        $node = new EmptyNode(0, 0, 20, 10);

        $this->assertTrue(
            $node->measure(Constraints::loose(new Size(100, 100)))->equals(new Size(20, 10)),
            'A loose offer should leave the node at its own size.',
        );
    }

    public function testMeasureIsClampedToTheOfferedRange(): void
    {
        $node = new EmptyNode(0, 0, 200, 200);

        $this->assertTrue(
            $node->measure(Constraints::loose(new Size(64, 32)))->equals(new Size(64, 32)),
            'A node may not answer larger than the offer.',
        );

        $this->assertTrue(
            $node->measure(Constraints::tight(new Size(8, 8)))->equals(new Size(8, 8)),
            'A tight offer decides outright.',
        );
    }

    /**
     * A tight constraint is what makes a node a layout boundary, so measuring one
     * cannot depend on what its children want.
     */
    public function testATightlyConstrainedNodeIgnoresItsOwnPreference(): void
    {
        $node = new EmptyNode(0, 0, 5, 5);
        $node->add(new EmptyNode(0, 0, 500, 500));

        $this->assertTrue($node->measure(Constraints::tight(new Size(32, 16)))->equals(new Size(32, 16)));
    }

    protected function stage(): Stage
    {
        return new Stage(UxHarness::presentation(
            new DirtyRegionsBuffer(64, 64, UxHarness::spec()),
            64,
            64,
        ));
    }
}
