<?php

namespace Fabricate\UX\Layout;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\UX\Node;

/**
 * A node that exists to place other nodes and paints nothing itself.
 *
 * Keeping layout and ink apart is what lets a tree be rearranged without any of
 * it becoming visible: a Row is structure, a Panel is paint, and a sketch
 * composes the two rather than getting a container that quietly draws a border.
 */
abstract class LayoutNode extends Node
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Deliberately empty. A layout node has no appearance of its own, so it also
     * has no business reporting itself opaque.
     */
    public function paint(DrawingSurface $surface): void
    {
        //
    }

    /**
     * The children that take part in layout.
     *
     * Hidden children are excluded rather than measured to zero, because a
     * container has to close the gap they leave — including the gap that would
     * otherwise sit next to them.
     *
     * @return array<int, Node>
     */
    protected function participants(): array
    {
        return array_values(array_filter(
            $this->children,
            static fn (Node $child): bool => $child->isVisible(),
        ));
    }

    /**
     * Half of $free, rounded away from zero, matching
     * {@see \Fabricate\NutsAndBolts\Geometry\Alignment} so a centred child lands
     * in the same place whichever of the two put it there.
     */
    protected function centred(int $free): int
    {
        return intdiv($free + (($free >= 0) ? 1 : -1), 2);
    }
}
