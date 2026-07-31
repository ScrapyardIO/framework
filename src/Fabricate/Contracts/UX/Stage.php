<?php

namespace Fabricate\Contracts\UX;

use Fabricate\Contracts\UX\Enums\Damage;
use Fabricate\NutsAndBolts\Geometry\Rect;

/**
 * The root of a UI tree and the only object that presents a frame.
 *
 * A stage tracks damaged *areas of the tree* and never individual pixels: the
 * framebuffer already coalesces pixel damage, and duplicating that state would
 * only let the two drift apart.
 */
interface Stage
{
    public function root(): ?Node;

    /**
     * Record that an area of renderer space needs attention.
     *
     * {@see Damage::LAYOUT} means a size actually changed and the subtree must be
     * remeasured; {@see Damage::PAINT} means only pixels changed, which is the
     * common case for a readout whose value moved but whose size did not.
     */
    public function invalidate(Rect $global, Damage $damage = Damage::PAINT): static;

    /**
     * Force the whole surface to be repainted on the next render.
     */
    public function invalidateAll(): static;

    /**
     * Register the node the next layout pass restarts from, which a node hands
     * over once {@see Node::markNeedsLayout()} has climbed to the nearest layout
     * boundary. A fixed-size subtree therefore costs a subtree walk rather than a
     * whole-tree one.
     */
    public function invalidateLayout(Node $boundary): static;

    public function needsLayout(): bool;

    /**
     * Bring the tree's geometry up to date. Runs before damage is collected,
     * because layout is what decides where a node is and damage is where it was
     * and where it went.
     */
    public function settleLayout(): static;

    public function isDirty(): bool;

    /**
     * The surface extent, which is also the tight offer the root is laid out
     * against — and therefore what makes the root a layout boundary.
     */
    public function width(): int;

    public function height(): int;

    /**
     * Repaint the damaged areas and present once.
     *
     * Returns false when there was nothing to do, having performed no paint calls
     * and no transmits — that return value is the contract a sketch loop relies on
     * to stay idle.
     */
    public function render(): bool;

    /**
     * Whether anything since the last render changed a size, as opposed to only
     * changing pixels.
     */
    public function damageLevel(): Damage;
}
