<?php

namespace Fabricate\UX\Input;

use Fabricate\Contracts\UX\Enums\FocusDirection;
use Fabricate\Contracts\UX\InputTarget;
use Fabricate\Contracts\UX\Node;
use Fabricate\Contracts\UX\Stage;

/**
 * Focus and the order it moves in.
 *
 * The order is derived from the tree on demand rather than registered by nodes,
 * so it cannot go stale: a node that was hidden, removed or disabled since the
 * last traversal is simply not in the list the next time one is asked for. The
 * cost is a walk per traversal, which happens on a button press and never per
 * frame.
 *
 * Tree order — depth-first, children in paint order — is the whole ordering
 * model. It matches what the eye sees for a `Row` or a `Column`, and a sketch
 * that wants a different order rearranges the tree.
 */
final class FocusRing
{
    protected ?InputTarget $focused = null;

    public function __construct(protected readonly Stage $stage) {}

    /**
     * Every node that can currently hold focus, in tree order.
     *
     * @return array<int, InputTarget>
     */
    public function order(): array
    {
        $root = $this->stage->root();

        if (is_null($root)) {
            return [];
        }

        $order = [];

        $this->collect($root, $order);

        return $order;
    }

    /**
     * The node currently holding focus, or null when nothing does.
     *
     * A node can stop being eligible without anything telling the ring — hidden,
     * detached or disabled since it was focused. That is discovered here rather
     * than prevented, because the alternative is every mutator on every node
     * knowing about focus, and the check is a walk up to the root rather than
     * over the tree. The node is told it lost focus at the moment it is noticed,
     * so an action button can never reach a control that is no longer there.
     */
    public function focused(): ?InputTarget
    {
        if (! is_null($this->focused) && ! $this->isEligible($this->focused)) {
            $this->focus(null);
        }

        return $this->focused;
    }

    /**
     * Move focus, notifying both sides. Focusing what is already focused does
     * nothing at all, so a node cannot be told it gained focus twice.
     */
    public function focus(?InputTarget $target): static
    {
        if ($this->focused === $target) {
            return $this;
        }

        $this->focused?->onFocusLost();
        $this->focused = $target;
        $this->focused?->onFocusGained();

        return $this;
    }

    public function clear(): static
    {
        return $this->focus(null);
    }

    public function next(): ?InputTarget
    {
        return $this->move(FocusDirection::NEXT);
    }

    public function previous(): ?InputTarget
    {
        return $this->move(FocusDirection::PREVIOUS);
    }

    /**
     * Step one place through the order, wrapping at both ends.
     *
     * With nothing focused, or with the focused node no longer in the order
     * because it was hidden or removed, traversal restarts from the end the
     * direction comes from rather than losing focus entirely.
     */
    public function move(FocusDirection $direction): ?InputTarget
    {
        $order = $this->order();
        $count = count($order);

        if ($count === 0) {
            $this->focus(null);

            return null;
        }

        $held = $this->focused();
        $current = is_null($held) ? false : array_search($held, $order, true);

        $index = ($current === false)
            ? $direction->entryIndex($count)
            : (($current + $direction->step() + $count) % $count);

        $this->focus($order[$index]);

        return $order[$index];
    }

    /**
     * Whether $target could still be reached by a traversal: it takes focus, and
     * every node between it and the stage root is visible.
     */
    protected function isEligible(InputTarget $target): bool
    {
        if (! $target->acceptsFocus()) {
            return false;
        }

        $node = $target;
        $root = $this->stage->root();

        while (! is_null($node)) {
            if (! $node->isVisible()) {
                return false;
            }

            if ($node === $root) {
                return true;
            }

            $node = $node->parent();
        }

        return false;
    }

    /**
     * Depth-first, children in paint order, parents before their children — the
     * same order the tree paints in, so the traversal matches the layout.
     *
     * @param  array<int, InputTarget>  $order
     */
    protected function collect(Node $node, array &$order): void
    {
        if (! $node->isVisible()) {
            return;
        }

        if (($node instanceof InputTarget) && $node->acceptsFocus()) {
            $order[] = $node;
        }

        foreach ($node->children() as $child) {
            $this->collect($child, $order);
        }
    }
}
