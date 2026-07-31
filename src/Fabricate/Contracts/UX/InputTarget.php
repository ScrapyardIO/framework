<?php

namespace Fabricate\Contracts\UX;

use Fabricate\Contracts\Actuation\HumanInput\TouchContact;
use Fabricate\NutsAndBolts\Geometry\Point;

/**
 * A node that wants input. Implementing this is what puts a node in the hit-test
 * and focus-traversal walks; a plain {@see Node} is inert and invisible to both.
 *
 * Every handler returns whether it consumed the event. Dispatch is depth-first
 * and topmost-first, stopping at the first node that returns true, which is what
 * makes one gesture reach exactly one node.
 *
 * Coordinates arriving here are always in *local* pixel space, with the origin at
 * the node's own top-left. Normalising touch and reconciling pointer coordinate
 * spaces happens during routing, so a node never sees a
 * {@see \Fabricate\Contracts\Actuation\HumanInput\CoordinateSpace}.
 */
interface InputTarget extends Node
{
    /**
     * Whether $local, already known to be inside the node's bounds, actually hits
     * it. The default for a rectangular node is simply true; a round gauge or a
     * node with a transparent margin can narrow it.
     */
    public function hitTest(Point $local): bool;

    /**
     * Whether this node can hold focus. False for a decorative or disabled node,
     * which is how it drops out of traversal without leaving a gap.
     */
    public function acceptsFocus(): bool;

    public function onTouch(TouchContact $contact, Point $local): bool;

    public function onPointer(Point $local, bool $pressed): bool;

    /**
     * A button press aimed at this node because it holds focus, identified by its
     * label so a game controller, a keyboard and a physical button pad all arrive
     * through the same path.
     */
    public function onButton(string $label): bool;

    public function onFocusGained(): void;

    public function onFocusLost(): void;
}
