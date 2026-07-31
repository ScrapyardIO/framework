<?php

namespace Fabricate\UX;

use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Framebuffers\FramebufferManager;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\Rendering\Renderer2D;

/**
 * What a node needs in order to paint through a cache, handed down by the stage
 * for the duration of one damage region.
 *
 * This exists so a {@see Node} can own its cache buffer without reaching into a
 * service container for a {@see FramebufferManager} or having to be told about the
 * surface it will eventually land on.
 *
 * A null context simply means paint directly, which is the normal path.
 */
final readonly class PaintContext
{
    /**
     * @param  Renderer2D  $renderer  retargeted at a node's cache while it fills it
     * @param  Framebuffer  $target  the surface's real framebuffer, restored after any retarget
     * @param  Rect  $region  the damage region being repainted, in renderer space
     * @param  bool  $caching_allowed  false when the surface cannot benefit, so ->cached() degrades to a direct paint
     */
    public function __construct(
        public Renderer2D $renderer,
        public Framebuffer $target,
        public FramebufferManager $buffers,
        public FormatSpec $spec,
        public Rect $region,
        public bool $caching_allowed,
    ) {}

    /**
     * Whether a node of these global bounds may be served from its cache.
     *
     * The containment requirement is not an optimisation, it is correctness:
     * {@see Framebuffer::blitFrom()} takes no source rectangle, so a cached node
     * can only be composited whole. Blitting a whole node when only part of it was
     * damaged would mark pixels dirty that nothing changed, and on a paged display
     * one spurious dirty page is a real 20-30 ms transmit.
     */
    public function allowsCacheFor(Rect $global): bool
    {
        return $this->caching_allowed && $this->region->containsRect($global);
    }
}
