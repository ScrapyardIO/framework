<?php

namespace Fabricate\UX\Input;

use Fabricate\Contracts\UX\InputTarget;
use Fabricate\Contracts\UX\Node;
use Fabricate\NutsAndBolts\Geometry\Point;

/**
 * Finds the node under a point.
 *
 * Depth-first and topmost-first: children are visited in reverse order, because
 * paint order runs first-to-last and the thing on top is therefore the thing
 * painted last. The first node that both contains the point and admits to being
 * hit wins, and nothing beneath it is consulted — that is what makes one gesture
 * reach exactly one node.
 *
 * A plain {@see Node} is invisible here. It is still descended into, so a layout
 * container never blocks the interactive nodes inside it, but it can never be a
 * target itself.
 */
final class HitTest
{
    /**
     * The topmost interactive node under $global, or null when the point misses
     * everything — a miss is absorbed rather than dispatched to the nearest
     * candidate, because a near miss on a touchscreen is not a press.
     */
    public static function at(Node $root, Point $global): ?Hit
    {
        if (! $root->isVisible()) {
            return null;
        }

        $bounds = $root->globalBounds();

        // Children are clipped to their parent, so a point outside this node
        // cannot be inside anything under it either.
        if (! $bounds->contains($global->x, $global->y)) {
            return null;
        }

        $children = $root->children();

        for ($index = count($children) - 1; $index >= 0; $index--) {
            $hit = self::at($children[$index], $global);

            if (! is_null($hit)) {
                return $hit;
            }
        }

        if (! $root instanceof InputTarget) {
            return null;
        }

        $local = new Point($global->x - $bounds->x, $global->y - $bounds->y);

        return $root->hitTest($local) ? new Hit($root, $local) : null;
    }
}
