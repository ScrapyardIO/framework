<?php

namespace Fabricate\UX\Input;

use Fabricate\Contracts\UX\InputTarget;
use Fabricate\NutsAndBolts\Geometry\Point;

/**
 * The node a gesture landed on, together with where it landed *inside* that
 * node.
 *
 * The pair travels together because a target without a local point is useless
 * to a slider and a local point without a target is useless to anybody, and
 * recomputing one from the other means walking the tree again.
 */
final readonly class Hit
{
    public function __construct(
        public InputTarget $target,
        public Point $local,
    ) {}
}
