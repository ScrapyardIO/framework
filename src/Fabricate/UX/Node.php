<?php

namespace Fabricate\UX;

use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\Enums\Damage;
use Fabricate\Contracts\UX\Node as NodeContract;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Framebuffers\FramebufferManager;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;

/**
 * An element in the retained UI tree.
 *
 * Bounds are *local*, relative to the parent's origin, so a subtree can be moved
 * by repositioning one node. Every mutator that changes what the node looks like
 * reports damage to the {@see Stage}; there are no signals and no tree diffing,
 * because a typed setter already knows exactly what changed and how much.
 *
 * Subclasses implement {@see paint()} and receive a surface whose origin is their
 * own top-left and which cannot paint outside their bounds.
 */
abstract class Node implements NodeContract
{
    protected ?Node $parent = null;

    /**
     * @var array<int, Node>
     */
    protected array $children = [];

    protected Rect $bounds;

    protected bool $visible = true;

    protected bool $mounted = false;

    protected bool $cached = false;

    /**
     * Allocated lazily on the first cached paint, because the format spec is a
     * property of the surface and is not known until then.
     */
    protected ?Framebuffer $cache = null;

    protected bool $cache_valid = false;

    /**
     * Starts true so a freshly built node is measured once before it paints.
     */
    protected bool $needs_layout = true;

    /**
     * The range the parent last offered. Kept because it is both the memo key for
     * {@see layout()} and the thing that decides whether this node is a layout
     * boundary: a tight offer means the parent already chose the size, so nothing
     * inside can change it.
     */
    protected ?Constraints $constraints = null;

    protected ?Size $measured = null;

    /**
     * Only ever set on a root node, by {@see Stage}. Descendants reach it by
     * walking up, so a subtree can be built detached and attached later without
     * having to thread the stage through every constructor.
     */
    protected ?Stage $stage = null;

    public function __construct(int $x = 0, int $y = 0, int $width = 0, int $height = 0)
    {
        $this->bounds = new Rect($x, $y, $width, $height);
    }

    abstract public function paint(DrawingSurface $surface): void;

    /**
     * Most nodes paint some of their box and leave the rest alone, so the safe
     * answer is no. A node that genuinely fills its bounds should override this
     * and say so: that is what lets a stage repaint from here instead of from the
     * root, and what restores a Panel background under a moving child.
     */
    public function isOpaque(): bool
    {
        return false;
    }

    /**
     * Answer a size within the offered range, and place any children while doing
     * it — measuring and positioning are one pass, because a container cannot
     * know its own extent without first asking its children for theirs.
     *
     * The default respects whatever size the node was given, clamped to what the
     * parent will accept, and imposes nothing on its children: a plain node is a
     * manual-positioning container, so its children keep the bounds they were
     * constructed with. Layout nodes and intrinsically-sized nodes override this.
     *
     * Call {@see layout()} rather than this — it is the memoised entry point that
     * also commits the answer to {@see bounds()}.
     */
    public function measure(Constraints $constraints): Size
    {
        foreach ($this->children as $child) {
            $child->layout(Constraints::unbounded());
        }

        return $constraints->constrain($this->size());
    }

    /**
     * Measure this node against $constraints and adopt the answer as its size.
     *
     * Re-measuring is skipped when nothing under this node changed and the offer
     * is the one it already answered, which is what stops a flex tree from
     * costing a full walk per frame. Pass $force to bypass the memo, which is how
     * the stage restarts layout at a boundary.
     */
    public function layout(Constraints $constraints, bool $force = false): Size
    {
        if (! $force
            && ! $this->needs_layout
            && ! is_null($this->measured)
            && $this->constraints?->equals($constraints) === true
        ) {
            return $this->measured;
        }

        $this->constraints = $constraints;

        $size = $constraints->constrain($this->measure($constraints));

        $this->measured = $size;
        $this->needs_layout = false;

        $this->applyLayoutSize($size);

        return $size;
    }

    /**
     * Re-run the last layout this node was given, which is what the stage does to
     * a boundary whose insides changed but whose offer did not.
     */
    public function relayout(): Size
    {
        return $this->layout($this->constraints ?? Constraints::tight($this->size()), true);
    }

    public function needsLayout(): bool
    {
        return $this->needs_layout;
    }

    /**
     * True when nothing inside this node can change its size, because the parent
     * offered exactly one.
     *
     * This is the whole reason flex layout stays cheap: a fixed-size subtree
     * absorbs its children's resizing instead of forwarding it to ancestors that
     * would have to be remeasured.
     */
    public function isLayoutBoundary(): bool
    {
        return $this->constraints?->isTight() === true;
    }

    /**
     * Report that this node has to be remeasured, and hand the stage the node
     * layout should restart from.
     *
     * The climb stops at the first boundary, or at the root when there is none.
     * There is no early exit on an already-marked node: a subtree can be marked
     * while detached and then attached, and the stage still has to be told.
     */
    public function markNeedsLayout(): static
    {
        $node = $this;

        while (! is_null($node->parent) && ! $node->isLayoutBoundary()) {
            $node->needs_layout = true;
            $node = $node->parent;
        }

        $node->needs_layout = true;
        $node->stage()?->invalidateLayout($node);

        return $this;
    }

    /**
     * Position this node within its parent, called by a layout parent once it has
     * measured everything and knows where each child goes.
     *
     * Damage only: a node that has just been measured is by definition the size
     * its parent asked for, so moving it cannot make anyone remeasure.
     */
    public function placeAt(int $x, int $y): static
    {
        if (($this->bounds->x === $x) && ($this->bounds->y === $y)) {
            return $this;
        }

        $vacated = $this->globalBounds();
        $this->bounds = new Rect($x, $y, $this->bounds->width, $this->bounds->height);

        return $this->damage($vacated)->damage($this->globalBounds());
    }

    /**
     * Hook for work that needs the tree to be live. Called once, on joining a
     * staged tree, and never again — reattaching elsewhere does not re-mount.
     */
    public function mount(): void
    {
        //
    }

    public function isMounted(): bool
    {
        return $this->mounted;
    }

    public function bounds(): Rect
    {
        return $this->bounds;
    }

    public function size(): Size
    {
        return new Size($this->bounds->width, $this->bounds->height);
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function parent(): ?Node
    {
        return $this->parent;
    }

    /**
     * @return array<int, Node>
     */
    public function children(): array
    {
        return $this->children;
    }

    public function root(): Node
    {
        $node = $this;

        while (! is_null($node->parent)) {
            $node = $node->parent;
        }

        return $node;
    }

    /**
     * Null while the subtree is detached, which is why every mutator has to
     * tolerate having nowhere to report damage.
     */
    public function stage(): ?Stage
    {
        return $this->root()->stage;
    }

    /**
     * Attach or detach the stage this tree reports damage to. Only meaningful on
     * a root node — {@see Stage::setRoot()} owns this, and descendants find the
     * stage by walking up rather than holding their own reference.
     */
    public function bindStage(?Stage $stage): static
    {
        $this->stage = $stage;

        if (! is_null($stage)) {
            $this->mountTree();
        }

        return $this;
    }

    /**
     * This node's origin in renderer space, accumulated up the tree.
     */
    public function globalOrigin(): Point
    {
        $x = 0;
        $y = 0;
        $node = $this;

        while (! is_null($node)) {
            $x += $node->bounds->x;
            $y += $node->bounds->y;
            $node = $node->parent;
        }

        return new Point($x, $y);
    }

    public function globalBounds(): Rect
    {
        $origin = $this->globalOrigin();

        return new Rect($origin->x, $origin->y, $this->bounds->width, $this->bounds->height);
    }

    /**
     * Move within the parent, damaging both the vacated and the newly occupied
     * area — the old one matters just as much, or the node leaves a ghost.
     *
     * Only PAINT damage: the node's size is unchanged, so no ancestor needs
     * remeasuring even though its position moved.
     */
    public function moveTo(int $x, int $y): static
    {
        if (($this->bounds->x === $x) && ($this->bounds->y === $y)) {
            return $this;
        }

        $vacated = $this->globalBounds();
        $this->bounds = new Rect($x, $y, $this->bounds->width, $this->bounds->height);

        return $this->damage($vacated)->invalidate(Damage::PAINT);
    }

    /**
     * A real size change, so this is the one mutator that forces LAYOUT damage.
     */
    public function resize(int $width, int $height): static
    {
        if (($this->bounds->width === $width) && ($this->bounds->height === $height)) {
            return $this;
        }

        $previous = $this->globalBounds();
        $this->bounds = new Rect($this->bounds->x, $this->bounds->y, $width, $height);

        return $this->markNeedsLayout()->damage($previous, Damage::LAYOUT)->invalidate(Damage::LAYOUT);
    }

    public function setBounds(Rect $bounds): static
    {
        if ($this->bounds->equals($bounds)) {
            return $this;
        }

        $resized = ($this->bounds->width !== $bounds->width) || ($this->bounds->height !== $bounds->height);
        $damage = $resized ? Damage::LAYOUT : Damage::PAINT;

        $previous = $this->globalBounds();
        $this->bounds = $bounds;

        if ($resized) {
            $this->markNeedsLayout();
        }

        return $this->damage($previous, $damage)->invalidate($damage);
    }

    public function show(): static
    {
        return $this->setVisible(true);
    }

    public function hide(): static
    {
        return $this->setVisible(false);
    }

    /**
     * LAYOUT damage, because a hidden node takes no space in a flex container and
     * its siblings have to redistribute.
     */
    public function setVisible(bool $visible): static
    {
        if ($this->visible === $visible) {
            return $this;
        }

        // Damage before hiding and after showing, so the rect is reported while
        // the node still counts as occupying it either way.
        $occupied = $this->globalBounds();
        $this->visible = $visible;

        // The parent, not this node: a hidden child takes no space in a flex
        // container, so it is the container that has to redistribute, and
        // marking from a child that is itself a boundary would stop short of it.
        ($this->parent ?? $this)->markNeedsLayout();

        return $this->damage($occupied, Damage::LAYOUT);
    }

    /**
     * Opt this node into its own buffer, so repainting it becomes a blit.
     *
     * Deliberately opt-in and rarely worth it. Measured on this stack, blitting a
     * small node out of a cache runs several times *slower* than simply painting
     * it again, because {@see Framebuffer::blitFrom()} is a per-pixel loop through
     * the contract. It only pays for a node whose own paint is genuinely
     * expensive — a sparkline recomputing a series, not a label drawing text.
     *
     * Two further limits, both enforced at paint time: caching is refused on a
     * surface that can only report whole-surface damage, and the cache is only
     * used when the damaged area covers the whole node, because blitFrom() takes
     * no source rectangle and would otherwise dirty pixels the damage never
     * touched.
     */
    public function cached(bool $cached = true): static
    {
        if ($this->cached === $cached) {
            return $this;
        }

        $this->cached = $cached;

        if (! $cached) {
            $this->cache = null;
        }

        $this->cache_valid = false;

        return $this;
    }

    public function isCached(): bool
    {
        return $this->cached;
    }

    public function add(Node ...$children): static
    {
        foreach ($children as $child) {
            $child->detach();
            $child->parent = $this;
            $this->children[] = $child;

            if (! is_null($this->stage())) {
                $child->mountTree();
            }

            $child->invalidate(Damage::LAYOUT);
        }

        // From here rather than from the child: a child that arrives already a
        // layout boundary would otherwise absorb the mark, leaving this container
        // unaware that it has gained something to make room for.
        return $this->markNeedsLayout();
    }

    public function remove(Node $child): static
    {
        $index = array_search($child, $this->children, true);

        if ($index === false) {
            return $this;
        }

        // Damage while the child can still report where it was.
        $child->invalidate(Damage::LAYOUT);
        unset($this->children[$index]);
        $this->children = array_values($this->children);
        $child->parent = null;

        return $this->markNeedsLayout();
    }

    /**
     * Remove from whatever parent this node currently has, if any.
     */
    public function detach(): static
    {
        $this->parent?->remove($this);

        return $this;
    }

    /**
     * Paint this node and then its children, each into its own derived surface.
     *
     * Subtrees that cannot land a pixel are skipped outright, which is what makes
     * a small damage rect cheap regardless of how large the tree is.
     */
    public function paintTree(Surface $surface, ?PaintContext $context = null): void
    {
        if (! $this->visible) {
            return;
        }

        if ($surface->isFullyClipped()) {
            return;
        }

        if ($this->servesFromCache($context)) {
            $this->paintThroughCache($context);

            return;
        }

        $surface->paint(function () use ($surface, $context): void {
            $this->paint($surface);

            foreach ($this->children as $child) {
                $child->paintTree($surface->forChild($child->bounds()), $context);
            }
        });
    }

    /**
     * Report that this node's own area needs the given kind of attention.
     *
     * Protected because invalidation is not part of the public vocabulary: typed
     * setters invalidate on the caller's behalf, and needing to call this by hand
     * means a setter is missing.
     */
    protected function invalidate(Damage $damage = Damage::PAINT): static
    {
        return $this->damage($this->globalBounds(), $damage);
    }

    /**
     * Report damage for an explicit renderer-space rect, which is how a mutator
     * reports the area a node has just *stopped* occupying.
     *
     * Any change inside a cached subtree makes that cache stale, so the caches of
     * this node and every ancestor are discarded on the way up. Without this a
     * cached node would happily blit the pixels it had before the change.
     */
    protected function damage(Rect $global, Damage $damage = Damage::PAINT): static
    {
        $node = $this;

        while (! is_null($node)) {
            $node->invalidateCache();
            $node = $node->parent;
        }

        $this->stage()?->invalidate($global, $damage);

        return $this;
    }

    /**
     * Adopt a size just answered by {@see measure()}.
     *
     * Damage is PAINT even though a size changed, because this runs *inside* the
     * layout pass — reporting LAYOUT here would re-dirty the tree the pass is in
     * the middle of settling and the stage would never come to rest.
     */
    protected function applyLayoutSize(Size $size): void
    {
        if (($this->bounds->width === $size->width) && ($this->bounds->height === $size->height)) {
            return;
        }

        $previous = $this->globalBounds();
        $this->bounds = new Rect($this->bounds->x, $this->bounds->y, $size->width, $size->height);

        $this->damage($previous)->damage($this->globalBounds());
    }

    /**
     * Discard cached pixels, so the next paint regenerates them.
     */
    protected function invalidateCache(): static
    {
        $this->cache_valid = false;

        return $this;
    }

    /**
     * Whether this paint can be served by a blit instead of a repaint. False
     * whenever the surface cannot benefit or the damage does not cover the whole
     * node, in which case ->cached() silently degrades to a direct paint — a
     * performance hint must never change what a sketch renders.
     */
    protected function servesFromCache(?PaintContext $context): bool
    {
        if (! $this->cached || is_null($context)) {
            return false;
        }

        if ($this->size()->isEmpty()) {
            return false;
        }

        return $context->allowsCacheFor($this->globalBounds());
    }

    /**
     * Fill the cache if it is stale, then composite it onto the real surface.
     *
     * Filling means pointing the renderer at the cache buffer for the duration,
     * which is why the restore sits in a finally: a node throwing mid-paint must
     * not leave the whole stage drawing into a discarded buffer.
     */
    protected function paintThroughCache(PaintContext $context): void
    {
        $cache = $this->cacheBuffer($context->buffers, $context->spec);

        if (! $this->hasValidCache()) {
            $local = $this->size()->atOrigin();

            $context->renderer->useFramebuffer($cache);

            try {
                $surface = Surface::forNode($context->renderer, $local, $local);

                $surface->paint(function () use ($surface): void {
                    $this->paint($surface);

                    // Nested caches are deliberately not honoured while filling a
                    // parent's cache: the inner blit would target the cache rather
                    // than the surface, and one cache per subtree is the point.
                    foreach ($this->children as $child) {
                        $child->paintTree($surface->forChild($child->bounds()));
                    }
                });
            } finally {
                $context->renderer->useFramebuffer($context->target);
            }

            $this->markCacheValid();
        }

        $global = $this->globalBounds();

        $context->target->blitFrom($cache, $global->x, $global->y);
    }

    /**
     * The node's cache buffer, allocated on first use at the node's current size.
     */
    public function cacheBuffer(FramebufferManager $manager, FormatSpec $spec): Framebuffer
    {
        $size = $this->size();

        if (is_null($this->cache)
            || ($this->cache->viewportWidth() !== $size->width)
            || ($this->cache->viewportHeight() !== $size->height)
        ) {
            $this->cache = $manager->make('full', max(1, $size->width), max(1, $size->height), $spec);
            $this->cache_valid = false;
        }

        return $this->cache;
    }

    public function hasValidCache(): bool
    {
        return $this->cache_valid && ! is_null($this->cache);
    }

    public function markCacheValid(): static
    {
        $this->cache_valid = true;

        return $this;
    }

    /**
     * Mount this node and everything under it, once each.
     */
    protected function mountTree(): void
    {
        if (! $this->mounted) {
            $this->mounted = true;
            $this->mount();
        }

        foreach ($this->children as $child) {
            $child->mountTree();
        }
    }
}
