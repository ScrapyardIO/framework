<?php

namespace Fabricate\UX;

use Fabricate\Contracts\Core\VisualPresentation;
use Fabricate\Contracts\UX\Enums\Damage;
use Fabricate\Contracts\UX\Node as NodeContract;
use Fabricate\Contracts\UX\Stage as StageContract;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Framebuffers\FramebufferManager;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\Rendering\Renderer2D;

/**
 * Binds the node tree to a real output and decides what actually gets repainted.
 *
 * The framebuffer is already the pixel-level damage tracker, so this class tracks
 * damaged *areas of the tree* and never individual pixels — duplicating the
 * buffer's dirty state would only let the two drift apart.
 *
 * Three behaviours carry the performance story:
 *
 * - Damage is coalesced and then snapped to the surface's
 *   {@see \Fabricate\Contracts\Framebuffers\Framebuffer::damageGranularity()}, so
 *   two small changes inside one SSD1306 page become one 20-30 ms transmit rather
 *   than two.
 * - Scattered damage is promoted to a single full-surface repaint past a
 *   threshold, because many small regions cost more in tree walks and transmit
 *   setup than one large one.
 * - A surface that reports it does not preserve contents across a present (any
 *   windowed SDL target) simply gets a full repaint whenever it paints, so
 *   sketches need no per-target branching.
 *
 * Erase comes from repainting the nearest opaque ancestor of each damaged area, so
 * a Panel restores its own background before its children paint over it. When no
 * opaque node covers the area there is nothing to erase with, so {@see background()}
 * is used as the backstop; setting it to null accepts ghosting in exchange for
 * painting over the previous frame.
 */
final class Stage implements StageContract
{
    protected ?Node $root = null;

    /**
     * Damaged areas in renderer space, un-coalesced until render time.
     *
     * @var array<int, Rect>
     */
    protected array $damage = [];

    /**
     * Starts true so the first frame paints the whole tree.
     */
    protected bool $needs_full_repaint = true;

    /**
     * The widest damage seen since the last render.
     */
    protected Damage $damage_level = Damage::PAINT;

    /**
     * Starts true so the first frame measures the tree before painting it.
     */
    protected bool $layout_dirty = true;

    /**
     * The nodes layout has to restart from: each one a layout boundary, or the
     * root when a change climbed all the way. Tracked as roots rather than as a
     * dirty flag on the stage so a fixed-size subtree costs a subtree walk and
     * not a whole-tree one.
     *
     * @var array<int, NodeContract>
     */
    protected array $relayout_roots = [];

    protected ?Color $background;

    protected FramebufferManager $buffers;

    /**
     * Promote to a full repaint past this many separate regions.
     */
    protected int $max_damage_regions = 8;

    /**
     * Promote to a full repaint once damage covers this percentage of the surface.
     */
    protected int $promotion_area_percent = 60;

    public function __construct(
        protected readonly VisualPresentation $presentation,
        ?Color $background = null,
        ?FramebufferManager $buffers = null,
    ) {
        $this->background = $background ?? Color::black();
        $this->buffers = $buffers ?? new FramebufferManager();
    }

    public function root(): ?Node
    {
        return $this->root;
    }

    public function setRoot(Node $root): static
    {
        $this->root?->bindStage(null);
        $this->root = $root;
        $root->bindStage($this);
        $root->markNeedsLayout();

        return $this->invalidateAll();
    }

    /**
     * Null disables erase-before-paint, for a caller that would rather paint over
     * the previous frame.
     */
    public function background(): ?Color
    {
        return $this->background;
    }

    public function setBackground(?Color $background): static
    {
        $this->background = $background;

        return $this->invalidateAll();
    }

    public function promotionThresholds(int $max_regions, int $area_percent): static
    {
        $this->max_damage_regions = max(1, $max_regions);
        $this->promotion_area_percent = min(100, max(1, $area_percent));

        return $this;
    }

    public function width(): int
    {
        return $this->presentation->width();
    }

    public function height(): int
    {
        return $this->presentation->height();
    }

    public function surfaceBounds(): Rect
    {
        return new Rect(0, 0, $this->width(), $this->height());
    }

    public function presentation(): VisualPresentation
    {
        return $this->presentation;
    }

    /**
     * The pixel format every {@see Color} in this tree resolves against.
     *
     * Painting hands a node a surface but not a format, because a surface speaks
     * in packed ints. A node holding a declared colour has to pack it somewhere,
     * and this is the only place that knows which depth it is packing for.
     */
    public function formatSpec(): FormatSpec
    {
        return $this->presentation->formatSpec();
    }

    public function renderer(): Renderer2D
    {
        return $this->presentation->getRenderer();
    }

    /**
     * A full-surface surface for measuring, not for painting.
     *
     * Intrinsic sizing needs {@see \Fabricate\Contracts\Rendering\DrawingSurface::getTextBounds()}
     * during {@see Node::measure()}, which happens before any node has a surface
     * of its own. Measuring through the renderer is safe because getTextBounds()
     * writes no pixels; it only reads the font state the caller just set.
     */
    public function measuringSurface(): Surface
    {
        return Surface::root($this->renderer(), $this->width(), $this->height());
    }

    /**
     * Record a damaged area in renderer space. Off-surface damage is dropped
     * rather than stored, so it cannot inflate a later coalesce.
     */
    public function invalidate(Rect $global, Damage $damage = Damage::PAINT): static
    {
        $this->damage_level = $this->damage_level->merge($damage);

        $clamped = $global->intersect($this->surfaceBounds());

        if ($clamped->isEmpty()) {
            return $this;
        }

        $this->damage[] = $clamped;

        return $this;
    }

    public function invalidateAll(): static
    {
        $this->needs_full_repaint = true;

        return $this;
    }

    /**
     * Register a node the next layout pass has to restart from.
     *
     * Called by {@see Node::markNeedsLayout()} once its climb has found the
     * nearest layout boundary, so the stage is handed the smallest subtree that
     * can absorb the change rather than being told to remeasure everything.
     */
    public function invalidateLayout(NodeContract $boundary): static
    {
        $this->layout_dirty = true;

        if (! in_array($boundary, $this->relayout_roots, true)) {
            $this->relayout_roots[] = $boundary;
        }

        return $this;
    }

    public function needsLayout(): bool
    {
        return $this->layout_dirty;
    }

    /**
     * Bring the tree's geometry up to date, before any damage is collected —
     * layout is what decides where nodes are, and damage is where they were and
     * where they went.
     *
     * A change under a fixed-size node only remeasures that subtree. Anything
     * that reached the root, or a root that has never been measured, costs one
     * full pass against the surface extent, which is also what makes the root a
     * boundary and stops relayout climbing past it.
     */
    public function settleLayout(): static
    {
        $roots = $this->relayout_roots;

        $this->layout_dirty = false;
        $this->relayout_roots = [];

        if (is_null($this->root)) {
            return $this;
        }

        if ($this->root->needsLayout()) {
            $this->root->layout(Constraints::tight(new Size($this->width(), $this->height())), true);

            return $this;
        }

        foreach ($roots as $node) {
            // A boundary can be detached between the mark and the pass, and
            // relayouting an orphan would place children nobody paints.
            if (! $this->isAttached($node)) {
                continue;
            }

            $node->relayout();
        }

        return $this;
    }

    /**
     * Whether $node still hangs off this stage's root.
     */
    protected function isAttached(NodeContract $node): bool
    {
        $current = $node;

        while (! is_null($current)) {
            if ($current === $this->root) {
                return true;
            }

            $current = $current->parent();
        }

        return false;
    }

    public function isDirty(): bool
    {
        return $this->needs_full_repaint || $this->layout_dirty || ($this->damage !== []);
    }

    /**
     * Whether anything since the last render actually changed a size, as opposed
     * to only changing pixels.
     */
    public function damageLevel(): Damage
    {
        return $this->damage_level;
    }

    /**
     * The areas a render would repaint right now: coalesced, snapped to the
     * surface's transmit granularity, then coalesced again because snapping can
     * push two regions into contact, and finally promoted to the whole surface if
     * there are too many of them or they cover too much of it.
     *
     * @return array<int, Rect>
     */
    public function damageRegions(): array
    {
        if ($this->needsFullRepaint()) {
            return [$this->surfaceBounds()];
        }

        if ($this->damage === []) {
            return [];
        }

        $granularity = $this->presentation->getFramebuffer()->damageGranularity();

        $snapped = array_map(
            fn (Rect $rect): Rect => $granularity->snap($rect),
            $this->coalesce($this->damage),
        );

        $regions = $this->coalesce($snapped);

        return $this->shouldPromote($regions) ? [$this->surfaceBounds()] : $regions;
    }

    /**
     * True when the whole surface has to be repainted: either something asked for
     * it, or the surface cannot be trusted to still hold the previous frame.
     *
     * Note this does not mean a frame happens — an idle frame stays idle even on a
     * non-preserving surface, because not presenting leaves the window showing the
     * frame it already has.
     */
    public function needsFullRepaint(): bool
    {
        return $this->needs_full_repaint
            || ! $this->presentation->getFramebuffer()->preservesContentsOnPresent();
    }

    /**
     * Repaint the damaged areas and present once.
     *
     * Returns false when there was nothing to do, having performed no paint calls
     * and no transmits at all.
     */
    public function render(): bool
    {
        if (! $this->isDirty()) {
            return false;
        }

        if (is_null($this->root)) {
            $this->resetDamage();

            return false;
        }

        $this->settleLayout();

        $regions = $this->damageRegions();

        $this->resetDamage();

        if ($regions === []) {
            return false;
        }

        foreach ($regions as $region) {
            $this->repaint($region);
        }

        $this->presentation->present();

        return true;
    }

    /**
     * Paint the damaged areas without presenting, for a caller driving the two
     * halves separately.
     *
     * @return array<int, Rect> the regions repainted
     */
    public function paintOnly(): array
    {
        // Same state-change gate as render(), or a non-preserving surface would
        // repaint in full on a frame where nothing changed.
        if (! $this->isDirty() || is_null($this->root)) {
            $this->resetDamage();

            return [];
        }

        $this->settleLayout();

        $regions = $this->damageRegions();

        $this->resetDamage();

        foreach ($regions as $region) {
            $this->repaint($region);
        }

        return $regions;
    }

    /**
     * Push the current frame out without repainting.
     */
    public function flush(): static
    {
        $this->presentation->present();

        return $this;
    }

    /**
     * Too many separate regions, or too much total coverage, and one full repaint
     * is cheaper than the per-region overhead.
     *
     * @param  array<int, Rect>  $regions
     */
    protected function shouldPromote(array $regions): bool
    {
        if (count($regions) > $this->max_damage_regions) {
            return true;
        }

        $surface_area = $this->width() * $this->height();

        if ($surface_area === 0) {
            return false;
        }

        $covered = array_sum(array_map(static fn (Rect $rect): int => $rect->area(), $regions));

        return (($covered * 100) / $surface_area) >= $this->promotion_area_percent;
    }

    protected function resetDamage(): void
    {
        $this->damage = [];
        $this->needs_full_repaint = false;
        $this->damage_level = Damage::PAINT;
        $this->layout_dirty = false;
        $this->relayout_roots = [];
    }

    protected function repaint(Rect $region): void
    {
        $renderer = $this->presentation->getRenderer();
        $root = $this->root;

        if (is_null($root)) {
            return;
        }

        $start = $this->repaintRootFor($region);

        $framebuffer = $this->presentation->getFramebuffer();

        $context = new PaintContext(
            $renderer,
            $framebuffer,
            $this->buffers,
            $this->presentation->formatSpec(),
            $region,
            ! $framebuffer->damageGranularity()->coversWholeSurface(),
        );

        $renderer->withClip($region, function () use ($region, $root, $start, $renderer, $context): void {
            // Nothing opaque covers this area, so the only thing that can erase
            // the previous frame is the stage background.
            if (is_null($start) && ! is_null($this->background)) {
                $renderer->fillRect(
                    $region->x,
                    $region->y,
                    $region->width,
                    $region->height,
                    $this->background->resolveFor($this->presentation->formatSpec()),
                );
            }

            $node = $start ?? $root;

            $node->paintTree(Surface::forNode($renderer, $node->globalBounds(), $region), $context);
        });
    }

    /**
     * The deepest node that can repaint $region on its own: opaque, covering the
     * whole region, and with nothing painted after it that could overlap.
     *
     * Descent only ever follows the *last* child that intersects the region, which
     * is what makes the second condition hold — anything after that child in paint
     * order misses the region entirely, so everything that still has to paint over
     * the region lives inside the subtree being returned.
     *
     * Null means no such node exists and the repaint has to start from the root.
     */
    protected function repaintRootFor(Rect $region): ?Node
    {
        $node = $this->root;

        if (is_null($node) || ! $node->isVisible() || ! $node->globalBounds()->containsRect($region)) {
            return null;
        }

        $cover = null;

        while (! is_null($node)) {
            if ($node->isOpaque()) {
                $cover = $node;
            }

            $next = $this->lastChildOver($node, $region);

            if (is_null($next) || ! $next->globalBounds()->containsRect($region)) {
                break;
            }

            $node = $next;
        }

        return $cover;
    }

    /**
     * The last visible child of $node whose bounds meet $region, i.e. the topmost
     * one painting there.
     */
    protected function lastChildOver(Node $node, Rect $region): ?Node
    {
        $found = null;

        foreach ($node->children() as $child) {
            if (! $child->isVisible()) {
                continue;
            }

            if (! $child->globalBounds()->intersect($region)->isEmpty()) {
                $found = $child;
            }
        }

        return $found;
    }

    /**
     * Merge every rect that overlaps or merely abuts another, matching the
     * adjacency rule {@see \Fabricate\Framebuffers\Strategy\DirtyRegionsBuffer}
     * uses, so the regions handed downstream are already in its terms.
     *
     * @param  array<int, Rect>  $rects
     * @return array<int, Rect>
     */
    protected function coalesce(array $rects): array
    {
        $merged = [];

        foreach ($rects as $rect) {
            if ($rect->isEmpty()) {
                continue;
            }

            // Repeat until the rect stops absorbing neighbours: one merge can
            // grow it into contact with a region it did not previously touch.
            $absorbing = true;

            while ($absorbing) {
                $absorbing = false;

                foreach ($merged as $index => $existing) {
                    if (! $rect->touches($existing)) {
                        continue;
                    }

                    $rect = $rect->union($existing);
                    unset($merged[$index]);
                    $absorbing = true;
                }
            }

            $merged[] = $rect;
        }

        return array_values($merged);
    }
}
