<?php

namespace Fabricate\UX\Layout;

use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Node;

/**
 * Forces a fixed extent on one or both axes, passing the other through.
 *
 * This is the node that creates layout boundaries. Fixing both axes hands the
 * child a tight offer, so nothing inside it can change its size and a change
 * down there stops climbing at the child instead of reaching the root. On a
 * status bar or a gauge that redraws constantly, that is the difference between
 * a subtree walk and a whole-tree one.
 */
final class Sized extends SingleChildNode
{
    public function __construct(
        protected ?int $fixed_width = null,
        protected ?int $fixed_height = null,
        ?Node $child = null,
    ) {
        parent::__construct($child);
    }

    public static function square(int $extent, ?Node $child = null): self
    {
        return new self($extent, $extent, $child);
    }

    public static function width(int $width, ?Node $child = null): self
    {
        return new self($width, null, $child);
    }

    public static function height(int $height, ?Node $child = null): self
    {
        return new self(null, $height, $child);
    }

    public function fixedWidth(): ?int
    {
        return $this->fixed_width;
    }

    public function fixedHeight(): ?int
    {
        return $this->fixed_height;
    }

    public function setFixedSize(?int $width, ?int $height): static
    {
        if (($this->fixed_width === $width) && ($this->fixed_height === $height)) {
            return $this;
        }

        $this->fixed_width = $width;
        $this->fixed_height = $height;

        return $this->markNeedsLayout();
    }

    public function measure(Constraints $constraints): Size
    {
        $width = is_null($this->fixed_width)
            ? null
            : max($constraints->min_width, min($constraints->max_width, $this->fixed_width));

        $height = is_null($this->fixed_height)
            ? null
            : max($constraints->min_height, min($constraints->max_height, $this->fixed_height));

        $child = $this->child();

        if (is_null($child)) {
            return $constraints->constrain(new Size($width ?? 0, $height ?? 0));
        }

        $inner = $child->layout(new Constraints(
            $width ?? 0,
            $width ?? $constraints->max_width,
            $height ?? 0,
            $height ?? $constraints->max_height,
        ));

        $child->placeAt(0, 0);

        return $constraints->constrain(new Size($width ?? $inner->width, $height ?? $inner->height));
    }
}
