<?php

namespace Fabricate\Contracts\UX;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;

/**
 * One element of a retained UI tree.
 *
 * Named Node rather than Component because Component already means a hardware
 * wrapper here (FanComponent, ServoComponent, DisplayComponent), and the overlap
 * would be permanent.
 *
 * The contract is deliberately narrow: it is what a {@see Stage} and a layout
 * parent need in order to measure, place and paint a node. Mutation is left to
 * the concrete class, because typed setters are what carry damage reporting and
 * an interface cannot express "every setter that changes appearance invalidates".
 */
interface Node
{
    /**
     * Bounds relative to the parent's origin, so moving a subtree means
     * repositioning exactly one node.
     */
    public function bounds(): Rect;

    public function size(): Size;

    /**
     * Origin in renderer space, accumulated up the tree.
     */
    public function globalOrigin(): Point;

    public function globalBounds(): Rect;

    public function isVisible(): bool;

    /**
     * True when this node paints every pixel of its own bounds.
     *
     * This is what makes erase-before-paint free: a stage repaints starting from
     * the nearest opaque ancestor of a damaged area, so an opaque Panel restores
     * its own background before its children paint over it. A node that lies here
     * will leave ghosts behind moving children.
     */
    public function isOpaque(): bool;

    public function parent(): ?Node;

    /**
     * Children in paint order — later children paint over earlier ones.
     *
     * @return array<int, Node>
     */
    public function children(): array;

    /**
     * Called once when the node joins a staged tree, for work that needs a real
     * surface to exist: allocating a cache buffer, resolving a font.
     */
    public function mount(): void;

    /**
     * Answer a size within $constraints, placing any children while doing it.
     *
     * The whole layout protocol is this one call: a parent offers a range, the
     * child answers inside it. There is no constraint solver and no second pass.
     *
     * Callers want {@see layout()}; this is the half a subclass implements.
     */
    public function measure(Constraints $constraints): Size;

    /**
     * Measure against $constraints and adopt the answer as this node's size.
     *
     * Memoised: an unchanged subtree offered the range it already answered is
     * skipped entirely, which is what keeps flex layout off the per-frame cost.
     */
    public function layout(Constraints $constraints, bool $force = false): Size;

    /**
     * Re-run the offer this node last answered, for a boundary whose insides
     * changed but whose offer did not.
     */
    public function relayout(): Size;

    public function needsLayout(): bool;

    /**
     * True when the parent offered exactly one size, so nothing inside this node
     * can change it. Relayout stops climbing here.
     */
    public function isLayoutBoundary(): bool;

    /**
     * Report that this node must be remeasured, handing the stage the boundary
     * layout should restart from.
     */
    public function markNeedsLayout(): static;

    /**
     * Position this node within its parent, in parent-local coordinates. Called
     * by a layout parent once it knows where everything goes.
     */
    public function placeAt(int $x, int $y): static;

    /**
     * Paint this node's own content, in local coordinates with the origin at its
     * top-left. Children are painted afterwards by the tree walk.
     *
     * The surface cannot paint outside the node's bounds, so implementations need
     * not range-check, and must not assume anything about where they sit.
     */
    public function paint(DrawingSurface $surface): void;
}
