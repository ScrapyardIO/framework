<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer;
use Fabricate\Framebuffers\Strategy\FullFramebuffer;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\UX\Stage;
use PHPUnit\Framework\TestCase;

/**
 * ->cached() is an opt-in performance hint, and these cases pin the two things
 * that matter about a hint: it must never change what is rendered, and it must
 * refuse itself wherever it cannot help.
 *
 * Worth knowing before reaching for it: measured on this stack, blitting a small
 * node out of a cache is several times *slower* than repainting it, because
 * blitFrom() is a per-pixel loop through the Framebuffer contract. It only pays
 * for a node whose own paint is genuinely expensive.
 */
class NodeCacheTest extends TestCase
{
    public function testCachingIsOffByDefaultAndOptIn(): void
    {
        $node = new FilledNode(0, 0, 8, 8);

        $this->assertFalse($node->isCached());
        $this->assertTrue($node->cached()->isCached());
        $this->assertFalse($node->cached(false)->isCached());
    }

    /**
     * The actual win: a repaint provoked by something *else* — here a full-surface
     * repaint — is served from the cache instead of re-running the node's paint.
     */
    public function testAWarmCacheSurvivesARepaintProvokedElsewhere(): void
    {
        [$stage, $root] = $this->stage();
        $node = new FilledNode(8, 8, 16, 16, 1);
        $node->cached();
        $root->add($node);

        $stage->render();

        $this->assertSame(1, $node->paint_count, 'The first frame has to fill the cache.');

        $stage->invalidateAll();
        $stage->render();

        $this->assertSame(1, $node->paint_count, 'A warm cache should be blitted, not repainted.');
    }

    /**
     * The counterpart, and the reason a cache cannot simply be kept: when the
     * node's own content changes, its cached pixels are stale and must be redrawn.
     * Blitting here would render the previous frame's contents forever.
     */
    public function testANodesOwnChangeDiscardsItsCache(): void
    {
        [$stage, $root] = $this->stage();
        $node = new FilledNode(8, 8, 16, 16, 1);
        $node->cached();
        $root->add($node);
        $stage->render();

        $this->assertSame(1, $node->paint_count);

        $node->touch();
        $stage->render();

        $this->assertSame(2, $node->paint_count, 'A stale cache was blitted instead of repainted.');
    }

    /**
     * A cache holds its children's pixels too, so a change deep inside a cached
     * subtree has to invalidate the cache at the top of it.
     *
     * The child fills its parent deliberately: its damage then covers the whole
     * cached node, so the cache really is eligible to be blitted and the only thing
     * that can force a repaint is the invalidation itself.
     */
    public function testAChangeInsideACachedSubtreeDiscardsTheCache(): void
    {
        [$stage, $root] = $this->stage();
        $node = new FilledNode(0, 0, 32, 32, 1);
        $inner = new FilledNode(0, 0, 32, 32, 0);
        $node->add($inner);
        $node->cached();
        $root->add($node);
        $stage->render();

        $this->assertSame(1, $node->paint_count);

        $inner->touch();
        $stage->render();

        $this->assertSame(2, $node->paint_count, 'A child changed but the ancestor kept blitting its old cache.');
    }

    /**
     * Caching must not change the pixels. Same tree, same geometry, cached and
     * uncached, compared byte for byte.
     */
    public function testACachedNodeRendersIdenticalPixels(): void
    {
        $uncached = $this->renderedPixels(false);
        $cached = $this->renderedPixels(true);

        $this->assertNotSame(
            array_fill(0, count($uncached), 0),
            $uncached,
            'The fixture painted nothing, so this comparison would pass vacuously.',
        );

        $this->assertSame($uncached, $cached, 'Enabling a cache changed what was rendered.');
    }

    /**
     * Resizing invalidates the cache, or the node would keep blitting its old
     * dimensions.
     */
    public function testResizingDiscardsTheCache(): void
    {
        [$stage, $root] = $this->stage();
        $node = new FilledNode(8, 8, 16, 16, 1);
        $node->cached();
        $root->add($node);
        $stage->render();

        $this->assertSame(1, $node->paint_count);

        $node->resize(20, 20);
        $stage->render();

        $this->assertSame(2, $node->paint_count, 'A resized node must refill its cache.');
    }

    /**
     * Invalidating the cache is not enough on its own — the buffer has to be
     * reallocated at the new size, or a node that grew would keep compositing a
     * cache too small to cover it and the new area would stay blank.
     */
    public function testAGrownNodeCompositesItsFullNewArea(): void
    {
        [$stage, $root, $buffer] = $this->stage();
        $node = new FilledNode(0, 0, 8, 8, 1);
        $node->cached();
        $root->add($node);
        $stage->render();

        $node->resize(16, 16);
        $stage->render();

        $this->assertSame(
            1,
            $buffer->getPixel(12, 12),
            'The grown area was never painted, so a stale undersized cache was composited.',
        );
    }

    /**
     * A surface that can only report whole-surface damage gains nothing from a
     * node-sized cache, so the hint is refused — silently, because a sketch must
     * render the same on every target with no per-target branching.
     */
    public function testCachingIsRefusedOnAWholeSurfaceGranularityTarget(): void
    {
        $buffer = new FullFramebuffer(64, 64, UxHarness::spec());
        $stage = new Stage(UxHarness::presentation($buffer, 64, 64));
        $root = new EmptyNode(0, 0, 64, 64);
        $stage->setRoot($root);

        $this->assertTrue($buffer->damageGranularity()->coversWholeSurface());

        $node = new FilledNode(8, 8, 16, 16, 1);
        $node->cached();
        $root->add($node);

        $stage->render();

        // invalidateAll() rather than touching the node, so its cache stays warm
        // and the only thing that can force a repaint is the refusal itself.
        $stage->invalidateAll();
        $stage->render();

        $this->assertSame(2, $node->paint_count, 'Caching should have been refused and the node repainted.');
    }

    /**
     * blitFrom() takes no source rectangle, so a cached node can only be
     * composited whole. When the damage covers just part of it, using the cache
     * would dirty pixels nothing changed — on a paged panel that is a wasted
     * 20-30 ms transmit — so the node repaints instead.
     */
    public function testPartialDamageBypassesTheCache(): void
    {
        [$stage, $root] = $this->stage();
        $node = new FilledNode(0, 0, 32, 32, 1);
        $node->cached();
        $root->add($node);
        $stage->render();

        $this->assertSame(1, $node->paint_count);

        // A region strictly inside the node, so the node is not fully covered.
        $stage->invalidate(new Rect(4, 4, 8, 8));
        $stage->render();

        $this->assertSame(2, $node->paint_count, 'Partial damage must repaint rather than blit the whole node.');
    }

    /**
     * A zero-sized node has nothing to cache and must not try to allocate a buffer.
     */
    public function testAnEmptyNodeIsNeverServedFromACache(): void
    {
        [$stage, $root] = $this->stage();
        $node = new FilledNode(4, 4, 0, 0, 1);
        $node->cached();
        $root->add($node);

        $stage->render();

        $this->assertSame(0, $node->paint_count, 'An empty node paints nothing at all.');
    }

    /**
     * Render the same nested tree with caching on or off and return every pixel,
     * so the comparison is on real output rather than on dump() objects, which
     * would only ever compare identities.
     *
     * @return array<int, int>
     */
    protected function renderedPixels(bool $cached): array
    {
        $buffer = new DirtyRegionsBuffer(64, 64, UxHarness::spec());
        $stage = new Stage(UxHarness::presentation($buffer, 64, 64));
        $root = new EmptyNode(0, 0, 64, 64);
        $stage->setRoot($root);

        $node = new FilledNode(8, 8, 16, 16, 0xF81F);
        $inner = new FilledNode(2, 2, 4, 4, 0x07E0);
        $node->add($inner);

        if ($cached) {
            $node->cached();
        }

        $root->add($node);
        $stage->paintOnly();

        $pixels = [];

        for ($y = 0; $y < 64; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $pixels[] = $buffer->getPixel($x, $y);
            }
        }

        return $pixels;
    }

    /**
     * @return array{0: Stage, 1: EmptyNode, 2: DirtyRegionsBuffer}
     */
    protected function stage(): array
    {
        $buffer = new DirtyRegionsBuffer(64, 64, UxHarness::spec());
        $stage = new Stage(UxHarness::presentation($buffer, 64, 64));
        $root = new EmptyNode(0, 0, 64, 64);
        $stage->setRoot($root);

        return [$stage, $root, $buffer];
    }
}
