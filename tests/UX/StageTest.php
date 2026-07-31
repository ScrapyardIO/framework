<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\UX\Enums\Damage;
use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\Framebuffers\Strategy\FullFramebuffer;
use Fabricate\Framebuffers\Strategy\PageSegmentBuffer;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\UX\Color;
use Fabricate\UX\Stage;
use PHPUnit\Framework\TestCase;

/**
 * Stage is where the performance story either works or does not.
 *
 * The measurements behind these cases: repainting a 128x64 tree costs ~0.3 ms,
 * while one SSD1306 page transmit costs 20-30 ms. So the assertions below are
 * about how many regions reach the buffer, never about how fast painting is.
 */
class StageTest extends TestCase
{
    protected const int SURFACE = 64;

    /**
     * Nothing has ever been painted, so the first frame cannot rely on retained
     * content and has to cover the whole surface.
     */
    public function testTheFirstFrameRepaintsEverything(): void
    {
        [$stage] = $this->retainedStage();

        $this->assertTrue($stage->isDirty());
        $this->assertEquals([[0, 0, 63, 63]], $this->regions($stage));
    }

    public function testAfterRenderingTheStageIsClean(): void
    {
        [$stage] = $this->retainedStage();

        $stage->render();

        $this->assertFalse($stage->isDirty());
        $this->assertSame([], $stage->damageRegions());
    }

    /**
     * Criterion: moving a node repaints its old and new position and nothing
     * else. Asserted on what the framebuffer marked dirty, which is what
     * actually gets transmitted.
     */
    public function testMovingANodeTransmitsOnlyTheOldAndNewPositions(): void
    {
        [$stage, $root, $buffer] = $this->retainedStage();
        $child = new FilledNode(4, 4, 8, 8);
        $root->add($child);
        $stage->render();
        $buffer->flush();

        $child->moveTo(40, 40);
        // paintOnly() rather than render(), because presenting flushes the very
        // dirty list this test needs to read.
        $stage->paintOnly();

        $this->assertSame(
            [[4, 4, 11, 11], [40, 40, 47, 47]],
            UxHarness::dirtyBounds($buffer),
        );
    }

    /**
     * Criterion: two changes inside one SSD1306 page produce exactly one dirty
     * page, because the page is the real unit of transmission.
     */
    public function testTwoChangesInOnePageCoalesceIntoASingleTransmit(): void
    {
        $buffer = new PageSegmentBuffer(128, 64, UxHarness::monoSpec());
        $stage = new Stage(UxHarness::presentation($buffer, 128, 64, UxHarness::monoSpec()));
        $root = new FilledNode(0, 0, 128, 64, 0);
        $stage->setRoot($root);

        // Both live inside rows 0-7, which is page 0.
        $left = new FilledNode(0, 1, 4, 4, 1);
        $right = new FilledNode(100, 1, 4, 4, 1);
        $root->add($left, $right);

        $stage->render();
        $buffer->flush();

        $left->moveTo(0, 2);
        $right->moveTo(100, 2);
        $stage->paintOnly();

        $this->assertCount(
            1,
            $buffer->dump(),
            'Two changes within one 8-row page must cost one transmit, not two.',
        );
    }

    /**
     * The counterpart: changes in different pages must stay separate, or the
     * coalescing above would just be "always send everything".
     */
    public function testChangesInDifferentPagesStaySeparate(): void
    {
        $buffer = new PageSegmentBuffer(128, 64, UxHarness::monoSpec());
        $stage = new Stage(UxHarness::presentation($buffer, 128, 64, UxHarness::monoSpec()));
        $root = new FilledNode(0, 0, 128, 64, 0);
        $stage->setRoot($root);

        $top = new FilledNode(0, 1, 4, 4, 1);
        $bottom = new FilledNode(0, 40, 4, 4, 1);
        $root->add($top, $bottom);

        $stage->render();
        $buffer->flush();

        $top->moveTo(0, 2);
        $bottom->moveTo(0, 41);
        $stage->paintOnly();

        $this->assertCount(2, $buffer->dump());
    }

    /**
     * Damage is snapped to the surface's transmit unit, so a 1px change on a
     * paged panel still reports a whole page rather than a single row.
     */
    public function testDamageIsSnappedToTheSurfaceGranularity(): void
    {
        $buffer = new PageSegmentBuffer(128, 64, UxHarness::monoSpec());
        $stage = new Stage(UxHarness::presentation($buffer, 128, 64, UxHarness::monoSpec()));
        $stage->setRoot(new FilledNode(0, 0, 128, 64, 0));
        $stage->render();

        $stage->invalidate(new Rect(10, 3, 1, 1));

        $this->assertEquals([[0, 0, 127, 7]], $this->regions($stage));
    }

    /**
     * A pixel-granularity surface must not have its damage inflated, or every
     * small change would cost a full-width band.
     */
    public function testPixelGranularitySurfacesKeepTightDamage(): void
    {
        [$stage] = $this->retainedStage();
        $stage->render();

        $stage->invalidate(new Rect(10, 3, 1, 1));

        $this->assertEquals([[10, 3, 10, 3]], $this->regions($stage));
    }

    /**
     * Criterion: a surface that does not preserve contents across a present gets
     * a full repaint every frame, so sketches need no per-target branching.
     * FullFramebuffer reports false because it clears on flush.
     */
    public function testASurfaceThatDoesNotRetainContentsAlwaysRepaintsEverything(): void
    {
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, UxHarness::spec());
        $stage = new Stage(UxHarness::presentation($buffer, self::SURFACE, self::SURFACE));
        $root = new FilledNode(0, 0, self::SURFACE, self::SURFACE);
        $stage->setRoot($root);
        $child = new FilledNode(4, 4, 8, 8);
        $root->add($child);

        $this->assertFalse($buffer->preservesContentsOnPresent());

        $stage->render();
        $child->moveTo(20, 20);

        $this->assertTrue($stage->needsFullRepaint());
        $this->assertEquals([[0, 0, 63, 63]], $this->regions($stage), 'A non-retaining surface must repaint in full.');
    }

    public function testARetainingSurfaceDoesNotAlwaysNeedAFullRepaint(): void
    {
        [$stage] = $this->retainedStage();

        $stage->render();

        $this->assertFalse($stage->needsFullRepaint());
    }

    /**
     * Criterion: moving a node leaves no ghost behind. This is the visual
     * counterpart of the damage assertions — the vacated pixels must actually be
     * erased, not merely reported as damaged.
     */
    public function testMovingANodeLeavesNoGhostBehind(): void
    {
        [$stage, $root, $buffer] = $this->retainedStage();
        $child = new FilledNode(4, 4, 8, 8);
        $root->add($child);
        $stage->render();

        $this->assertSame([4, 4, 11, 11], $this->painted($buffer)?->toBounds());

        $child->moveTo(40, 40);
        $stage->render();

        $this->assertSame(
            [40, 40, 47, 47],
            $this->painted($buffer)?->toBounds(),
            'The old position was still lit, so the move left a ghost.',
        );
    }

    /**
     * Erase happens because the stage paints its background over the damaged area
     * first. With no background there is nothing to erase with, so the ghost
     * returning here proves the erase is what does the work.
     */
    public function testWithoutABackgroundTheVacatedAreaIsNotErased(): void
    {
        [$stage, $root, $buffer] = $this->retainedStage();
        $stage->setBackground(null);
        $child = new FilledNode(4, 4, 8, 8);
        $root->add($child);
        $stage->render();

        $child->moveTo(40, 40);
        $stage->render();

        $this->assertSame([4, 4, 47, 47], $this->painted($buffer)?->toBounds());
    }

    public function testTheBackgroundIsResolvedAgainstTheSurfaceFormat(): void
    {
        [$stage] = $this->retainedStage();

        $this->assertTrue($stage->background()?->equals(Color::black()));

        $stage->setBackground(Color::white());

        $this->assertTrue($stage->background()?->equals(Color::white()));
    }

    /**
     * Subtrees that cannot land a pixel in the damaged region are skipped, which
     * is what keeps a small repaint cheap on a large tree.
     */
    public function testNodesOutsideTheDamagedRegionAreNotPainted(): void
    {
        [$stage, $root] = $this->retainedStage();
        $near = new FilledNode(0, 0, 4, 4);
        $far = new FilledNode(50, 50, 4, 4);
        $root->add($near, $far);
        $stage->render();

        $near->paint_count = 0;
        $far->paint_count = 0;

        $near->touch();
        $stage->render();

        $this->assertSame(1, $near->paint_count);
        $this->assertSame(0, $far->paint_count, 'A node far from the damage was repainted anyway.');
    }

    /**
     * A hidden child of a painted parent still costs nothing.
     */
    public function testDamageOutsideTheSurfaceIsDropped(): void
    {
        [$stage] = $this->retainedStage();
        $stage->render();

        $stage->invalidate(new Rect(200, 200, 10, 10));

        $this->assertFalse($stage->isDirty(), 'Off-surface damage should never be recorded.');
    }

    public function testDamagePartlyOffSurfaceIsClampedNotDropped(): void
    {
        [$stage] = $this->retainedStage();
        $stage->render();

        $stage->invalidate(new Rect(60, 60, 20, 20));

        $this->assertEquals([[60, 60, 63, 63]], $this->regions($stage));
    }

    /**
     * Overlapping and merely-abutting damage collapses, matching the adjacency
     * rule the dirty-region buffer itself uses.
     */
    public function testAbuttingDamageRegionsCoalesce(): void
    {
        [$stage] = $this->retainedStage();
        $stage->render();

        $stage->invalidate(new Rect(0, 0, 8, 8));
        $stage->invalidate(new Rect(8, 0, 8, 8));

        $this->assertEquals([[0, 0, 15, 7]], $this->regions($stage));
    }

    public function testDistantDamageRegionsStaySeparate(): void
    {
        [$stage] = $this->retainedStage();
        $stage->render();

        $stage->invalidate(new Rect(0, 0, 4, 4));
        $stage->invalidate(new Rect(40, 40, 4, 4));

        $this->assertEquals([[0, 0, 3, 3], [40, 40, 43, 43]], $this->regions($stage));
    }

    /**
     * One merge can grow a region into contact with a third it did not previously
     * touch, so coalescing has to keep going until nothing more is absorbed.
     */
    public function testARegionBridgingTwoOthersCollapsesAllThree(): void
    {
        [$stage] = $this->retainedStage();
        $stage->render();

        $stage->invalidate(new Rect(0, 0, 4, 4));
        $stage->invalidate(new Rect(20, 0, 4, 4));
        $stage->invalidate(new Rect(4, 0, 16, 4));

        $this->assertEquals([[0, 0, 23, 3]], $this->regions($stage));
    }

    public function testAStageWithNoRootRendersHarmlessly(): void
    {
        $buffer = new DirtyRegionsBuffer(self::SURFACE, self::SURFACE, UxHarness::spec());
        $stage = new Stage(UxHarness::presentation($buffer, self::SURFACE, self::SURFACE));

        $this->assertFalse($stage->render(), 'A rootless stage has nothing to paint or transmit.');
        $this->assertFalse($stage->isDirty());
        $this->assertNull($stage->root());
    }

    public function testReplacingTheRootRebindsTheStageAndForcesAFullRepaint(): void
    {
        [$stage, $root] = $this->retainedStage();
        $stage->render();

        $replacement = new FilledNode(0, 0, self::SURFACE, self::SURFACE);
        $stage->setRoot($replacement);

        $this->assertSame($replacement, $stage->root());
        $this->assertSame($stage, $replacement->stage());
        $this->assertNull($root->stage(), 'The old root should no longer report damage here.');
        $this->assertTrue($stage->needsFullRepaint());
    }

    /**
     * Asserted against the display actually bound to the stage, counting the
     * buffers that reach it. Each damaged region must arrive exactly once — a
     * region transmitted twice is the expensive failure on an I2C panel, where
     * every 128x8 page costs 20-30 ms.
     *
     * Note this counts transmitted buffers rather than present() calls: one
     * present hands the display one buffer per damaged region.
     */
    public function testEachDamagedRegionIsTransmittedExactlyOnce(): void
    {
        $buffer = new DirtyRegionsBuffer(self::SURFACE, self::SURFACE, UxHarness::spec());
        $display = new UxTestDisplay(self::SURFACE, self::SURFACE, UxHarness::spec());
        $stage = new Stage(UxHarness::presentation($buffer, self::SURFACE, self::SURFACE, null, $display));
        $root = new FilledNode(0, 0, self::SURFACE, self::SURFACE);
        $stage->setRoot($root);

        $this->assertSame(0, $display->flush_count);
        $this->assertTrue($stage->render(), 'A dirty stage reports that it did work.');
        $this->assertFalse($stage->isDirty(), 'render() must present as well as paint.');
        $this->assertSame(1, $display->flush_count, 'One full-surface frame is one transmit.');

        $near = new FilledNode(0, 0, 4, 4);
        $far = new FilledNode(40, 40, 4, 4);
        $root->add($near, $far);
        $stage->render();

        $this->assertSame(3, $display->flush_count, 'Two separate regions must cost exactly two transmits.');

        $this->assertFalse($stage->render(), 'An idle frame must not present.');
        $this->assertSame(3, $display->flush_count, 'An idle frame reached the display.');
    }

    /**
     * Criterion: a frame with no state change performs zero paint calls and zero
     * transmits. This is the assertion a sketch loop's idle behaviour rests on.
     */
    public function testAnUnchangedFrameDoesNoWorkAtAll(): void
    {
        [$stage, $root, $buffer] = $this->retainedStage();
        $child = new FilledNode(4, 4, 8, 8);
        $root->add($child);

        $this->assertTrue($stage->render());

        $child->paint_count = 0;
        $root->paint_count = 0;
        $buffer->flush();

        $this->assertFalse($stage->render(), 'An unchanged stage must report that it did nothing.');
        $this->assertSame(0, $child->paint_count, 'An idle frame painted a node.');
        $this->assertSame(0, $root->paint_count, 'An idle frame painted the root.');
        $this->assertSame([], $buffer->dump(), 'An idle frame marked something for transmission.');
    }

    /**
     * The harder half of the idle case. A non-preserving surface always wants a
     * *full* repaint when it paints at all, so without a state-change gate every
     * idle frame would repaint and transmit the entire surface. Not presenting is
     * safe: the window keeps showing the frame it already has.
     */
    public function testAnUnchangedFrameIsIdleEvenOnANonPreservingSurface(): void
    {
        $buffer = new FullFramebuffer(self::SURFACE, self::SURFACE, UxHarness::spec());
        $stage = new Stage(UxHarness::presentation($buffer, self::SURFACE, self::SURFACE));
        $root = new FilledNode(0, 0, self::SURFACE, self::SURFACE);
        $stage->setRoot($root);

        $this->assertFalse($buffer->preservesContentsOnPresent());
        $this->assertTrue($stage->render());

        $root->paint_count = 0;

        $this->assertFalse($stage->render(), 'An idle frame must do nothing even here.');
        $this->assertSame(0, $root->paint_count, 'A non-preserving surface repainted an unchanged frame.');
    }

    /**
     * Setting the same value twice is not a state change, so the second frame must
     * still be idle.
     */
    public function testARedundantSetterDoesNotProvokeAFrame(): void
    {
        [$stage, $root] = $this->retainedStage();
        $child = new FilledNode(4, 4, 8, 8);
        $root->add($child);
        $stage->render();

        $child->moveTo(4, 4);

        $this->assertFalse($stage->render());
    }

    /**
     * The point of isOpaque(): a Panel that fills its own bounds restores its
     * background under a moving child, so erasing costs nothing extra and does not
     * depend on the stage having a background at all.
     */
    public function testAnOpaqueAncestorErasesWithoutAStageBackground(): void
    {
        [$stage, $root, $buffer] = $this->retainedStage();
        $stage->setBackground(null);

        $panel = (new FilledNode(0, 0, self::SURFACE, self::SURFACE, 0))->opaque();
        $child = new FilledNode(4, 4, 8, 8, 1);
        $root->add($panel);
        $panel->add($child);
        $stage->render();

        $child->moveTo(40, 40);
        $stage->render();

        $this->assertSame(
            [40, 40, 47, 47],
            $this->painted($buffer)?->toBounds(),
            'The opaque panel should have repainted its own background over the vacated area.',
        );
    }

    /**
     * Repaint starts at the opaque node rather than the root, so an unrelated
     * sibling above it in the tree is not walked at all.
     */
    public function testRepaintStartsAtTheNearestOpaqueAncestor(): void
    {
        [$stage, $root] = $this->retainedStage();

        $panel = (new FilledNode(0, 0, 32, 32, 0))->opaque();
        $child = new FilledNode(4, 4, 8, 8, 1);
        $root->add($panel);
        $panel->add($child);
        $stage->render();

        $root->paint_count = 0;
        $panel->paint_count = 0;

        $child->touch();
        $stage->render();

        $this->assertSame(1, $panel->paint_count, 'The opaque panel is the repaint root.');
        $this->assertSame(0, $root->paint_count, 'The root is above the opaque ancestor and should be skipped.');
    }

    /**
     * A later sibling drawn over the damaged area must not be skipped, or it would
     * disappear whenever the node beneath it was repainted. This is why descent
     * only follows the last child meeting the region.
     */
    public function testALaterSiblingOverTheDamageIsStillPainted(): void
    {
        [$stage, $root] = $this->retainedStage();

        $panel = (new FilledNode(0, 0, 32, 32, 0))->opaque();
        $overlay = new FilledNode(0, 0, 32, 32, 1);
        $root->add($panel, $overlay);
        $stage->render();

        $panel->paint_count = 0;
        $overlay->paint_count = 0;

        $panel->touch();
        $stage->render();

        $this->assertSame(1, $overlay->paint_count, 'A sibling painting over the damage was skipped.');
    }

    /**
     * An opaque node that covers only *part* of the damaged area cannot be the
     * repaint root: everything outside it would go unpainted and unerased. So
     * descent stops at the last node that still contains the whole region.
     */
    public function testAnOpaqueNodeCoveringOnlyPartOfTheDamageIsNotTheRepaintRoot(): void
    {
        [$stage, $root, $buffer] = $this->retainedStage();

        $panel = (new FilledNode(0, 0, 32, 32, 0))->opaque();
        $root->add($panel);
        $stage->render();

        // Something lit outside the panel, which the repaint has to erase.
        $buffer->setPixel(34, 34, 1);

        // Straddles the panel's right and bottom edges at 31.
        $stage->invalidate(new Rect(24, 24, 16, 16));
        $stage->render();

        $this->assertSame(
            0,
            $buffer->getPixel(34, 34),
            'Damage outside the opaque panel was never erased, so the panel was wrongly used as the repaint root.',
        );
    }

    /**
     * Many scattered regions cost more in tree walks and transmit setup than one
     * large one, so past a threshold the stage stops being clever.
     */
    public function testScatteredDamageIsPromotedToAFullRepaint(): void
    {
        [$stage] = $this->retainedStage();
        $stage->render();
        $stage->promotionThresholds(4, 90);

        foreach ([0, 8, 16, 24, 32] as $offset) {
            $stage->invalidate(new Rect($offset, $offset, 2, 2));
        }

        $this->assertEquals(
            [[0, 0, 63, 63]],
            $this->regions($stage),
            'Five separate regions against a threshold of four should promote.',
        );
    }

    public function testDamageUnderTheThresholdIsNotPromoted(): void
    {
        [$stage] = $this->retainedStage();
        $stage->render();
        $stage->promotionThresholds(4, 90);

        $stage->invalidate(new Rect(0, 0, 2, 2));
        $stage->invalidate(new Rect(40, 40, 2, 2));

        $this->assertCount(2, $stage->damageRegions());
    }

    /**
     * Coverage promotes too, since repainting most of the surface in pieces is
     * strictly worse than repainting it once.
     */
    public function testDamageCoveringMostOfTheSurfaceIsPromoted(): void
    {
        [$stage] = $this->retainedStage();
        $stage->render();
        $stage->promotionThresholds(8, 50);

        $stage->invalidate(new Rect(0, 0, self::SURFACE, 40));

        $this->assertEquals([[0, 0, 63, 63]], $this->regions($stage));
    }

    /**
     * Layout and paint dirtiness are tracked separately, because a readout whose
     * value changed but whose size did not is the common case and must not cost a
     * remeasure.
     */
    public function testDamageLevelDistinguishesPaintFromLayout(): void
    {
        [$stage, $root] = $this->retainedStage();
        $child = new FilledNode(4, 4, 8, 8);
        $root->add($child);
        $stage->render();

        $this->assertSame(Damage::PAINT, $stage->damageLevel(), 'A rendered stage resets to PAINT.');

        $child->moveTo(20, 20);

        $this->assertSame(Damage::PAINT, $stage->damageLevel(), 'Moving changes no size.');

        $stage->render();
        $child->resize(16, 16);

        $this->assertSame(Damage::LAYOUT, $stage->damageLevel(), 'Resizing is a real size change.');
    }

    public function testStageReportsItsSurfaceExtent(): void
    {
        [$stage] = $this->retainedStage();

        $this->assertSame(self::SURFACE, $stage->width());
        $this->assertSame(self::SURFACE, $stage->height());
        $this->assertSame([0, 0, 63, 63], $stage->surfaceBounds()->toBounds());
    }

    /**
     * A retained, pixel-granularity surface: the SSD1306-like case where partial
     * repaint is both possible and worth doing.
     *
     * @return array{0: Stage, 1: EmptyNode, 2: DirtyRegionsBuffer}
     */
    protected function retainedStage(): array
    {
        $buffer = new DirtyRegionsBuffer(self::SURFACE, self::SURFACE, UxHarness::spec());
        $stage = new Stage(UxHarness::presentation($buffer, self::SURFACE, self::SURFACE));
        // The root paints nothing at all, so any erasing observed in these tests
        // is the stage's background doing it and not a root that happens to fill
        // with zero.
        $root = new EmptyNode(0, 0, self::SURFACE, self::SURFACE);
        $stage->setRoot($root);

        return [$stage, $root, $buffer];
    }

    protected function painted(DirtyRegionsBuffer $buffer): ?Rect
    {
        return UxHarness::paintedBounds($buffer, self::SURFACE, self::SURFACE);
    }

    /**
     * @return array<int, array{0: int, 1: int, 2: int, 3: int}>
     */
    protected function regions(Stage $stage): array
    {
        $bounds = array_map(fn (Rect $rect): array => $rect->toBounds(), $stage->damageRegions());

        sort($bounds);

        return $bounds;
    }
}
