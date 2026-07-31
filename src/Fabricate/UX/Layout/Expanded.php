<?php

namespace Fabricate\UX\Layout;

use Fabricate\Contracts\UX\Flexible;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Node;

/**
 * Claims a share of a flex container's leftover main-axis space for its child.
 *
 * Transparent otherwise: whatever the container offers is passed straight
 * through, so the child ends up tight along the main axis and free across it.
 * Outside a {@see Flex} this is an ordinary wrapper and the weight is ignored.
 */
final class Expanded extends SingleChildNode implements Flexible
{
    public function __construct(?Node $child = null, protected int $weight = 1)
    {
        parent::__construct($child);
    }

    public function flex(): int
    {
        return max(0, $this->weight);
    }

    /**
     * Zero opts back out of the split, and the node is measured as an ordinary
     * inflexible child.
     */
    public function weigh(int $weight): static
    {
        $weight = max(0, $weight);

        if ($this->weight === $weight) {
            return $this;
        }

        $this->weight = $weight;

        return $this->markNeedsLayout();
    }

    public function measure(Constraints $constraints): Size
    {
        $child = $this->child();

        if (is_null($child)) {
            return $constraints->constrain(Size::zero());
        }

        $size = $child->layout($constraints);
        $child->placeAt(0, 0);

        return $constraints->constrain($size);
    }
}
