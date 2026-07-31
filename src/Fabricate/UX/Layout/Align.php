<?php

namespace Fabricate\UX\Layout;

use Fabricate\NutsAndBolts\Geometry\Alignment;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Node;

/**
 * Positions a shrink-wrapped child inside the space the parent offered.
 *
 * Expands to fill every bounded axis, because alignment is meaningless in a box
 * that has collapsed onto its content. On an unbounded axis there is nothing to
 * align against, so that axis shrink-wraps instead.
 *
 * This is what replaces the hand-rolled `intdiv($width - $textWidth, 2)` that
 * every sketch currently open-codes.
 */
final class Align extends SingleChildNode
{
    protected Alignment $alignment;

    public function __construct(?Alignment $alignment = null, ?Node $child = null)
    {
        parent::__construct($child);

        $this->alignment = $alignment ?? Alignment::center();
    }

    public static function centered(?Node $child = null): self
    {
        return new self(Alignment::center(), $child);
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
        $child = $this->child();
        $inner = is_null($child) ? Size::zero() : $child->layout($constraints->loosened());

        $size = $constraints->constrain(new Size(
            $constraints->hasBoundedWidth() ? $constraints->max_width : $inner->width,
            $constraints->hasBoundedHeight() ? $constraints->max_height : $inner->height,
        ));

        if (! is_null($child)) {
            $placed = $this->alignment->positionIn($size->atOrigin(), $inner);
            $child->placeAt($placed->x, $placed->y);
        }

        return $size;
    }
}
