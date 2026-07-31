<?php

namespace Fabricate\UX\Layout;

use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\EdgeInsets;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Node;

/**
 * Insets its child by a fixed margin on each edge.
 *
 * The offer is deflated rather than loosened, so padding inside a tight box
 * still forces the child to fill what is left of it — which is what makes
 * `Padding` around an opaque `Panel` behave the way a border does, instead of
 * collapsing the panel to its content.
 */
final class Padding extends SingleChildNode
{
    public function __construct(protected EdgeInsets $insets, ?Node $child = null)
    {
        parent::__construct($child);
    }

    public static function all(int $inset, ?Node $child = null): self
    {
        return new self(EdgeInsets::all($inset), $child);
    }

    public static function symmetric(int $horizontal = 0, int $vertical = 0, ?Node $child = null): self
    {
        return new self(EdgeInsets::symmetric($horizontal, $vertical), $child);
    }

    public function insets(): EdgeInsets
    {
        return $this->insets;
    }

    public function setInsets(EdgeInsets $insets): static
    {
        if ($this->insets->equals($insets)) {
            return $this;
        }

        $this->insets = $insets;

        return $this->markNeedsLayout();
    }

    public function measure(Constraints $constraints): Size
    {
        $child = $this->child();

        if (is_null($child)) {
            return $constraints->constrain(new Size($this->insets->horizontal(), $this->insets->vertical()));
        }

        $inner = $child->layout($constraints->deflate($this->insets));
        $child->placeAt($this->insets->left, $this->insets->top);

        return $constraints->constrain(new Size(
            $inner->width + $this->insets->horizontal(),
            $inner->height + $this->insets->vertical(),
        ));
    }
}
