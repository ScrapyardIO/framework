<?php

namespace Fabricate\UX\Layout;

use Fabricate\NutsAndBolts\Geometry\Alignment;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;

/**
 * Overlays its children in one box, later children on top of earlier ones.
 *
 * Z-order is child order, which is already how the paint walk works, so a stack
 * introduces no new ordering concept — it only stops the children from being
 * spread along an axis.
 *
 * Ordinary children are shrink-wrapped and then placed by the stack's
 * alignment; {@see Positioned} children opt out and pin themselves to the
 * stack's edges instead. The stack sizes itself to its largest ordinary child,
 * so a positioned child can never inflate the box it is positioned against.
 */
final class Stack extends LayoutNode
{
    protected Alignment $alignment;

    public function __construct(?Alignment $alignment = null)
    {
        parent::__construct();

        $this->alignment = $alignment ?? Alignment::topLeft();
    }

    public function alignment(): Alignment
    {
        return $this->alignment;
    }

    public function setAlignment(Alignment $alignment): static
    {
        if ($this->alignment->equals($alignment)) {
            return $this;
        }

        $this->alignment = $alignment;

        return $this->markNeedsLayout();
    }

    public function measure(Constraints $constraints): Size
    {
        $children = $this->participants();
        $loose = $constraints->loosened();

        $width = $constraints->min_width;
        $height = $constraints->min_height;
        $sizes = [];

        foreach ($children as $index => $child) {
            if ($child instanceof Positioned) {
                continue;
            }

            $sizes[$index] = $child->layout($loose);
            $width = max($width, $sizes[$index]->width);
            $height = max($height, $sizes[$index]->height);
        }

        $size = $constraints->constrain(new Size($width, $height));
        $area = $size->atOrigin();

        foreach ($children as $index => $child) {
            if ($child instanceof Positioned) {
                $child->layoutIn($area);

                continue;
            }

            $placed = $this->alignment->positionIn($area, $sizes[$index]);
            $child->placeAt($placed->x, $placed->y);
        }

        return $size;
    }
}
