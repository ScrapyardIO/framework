<?php

namespace Fabricate\UX\Layout;

use Fabricate\UX\Node;

/**
 * A layout node that decorates exactly one child: padding, alignment, a fixed
 * size, a flex share.
 *
 * Extra children are not rejected — {@see Node::add()} stays uniform — but only
 * the first visible one is laid out, so hiding a child collapses the wrapper
 * rather than leaving it holding an invisible box.
 */
abstract class SingleChildNode extends LayoutNode
{
    public function __construct(?Node $child = null)
    {
        parent::__construct();

        if (! is_null($child)) {
            $this->add($child);
        }
    }

    public function child(): ?Node
    {
        foreach ($this->children as $child) {
            if ($child->isVisible()) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Replace whatever this node was wrapping. Null empties it.
     */
    public function setChild(?Node $child): static
    {
        foreach ($this->children as $existing) {
            $this->remove($existing);
        }

        return is_null($child) ? $this : $this->add($child);
    }
}
