<?php

namespace Fabricate\UX\Layout;

use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Node;

/**
 * The absolute escape hatch: pins its child to edges of the enclosing
 * {@see Stack} rather than taking part in flow.
 *
 * Any subset of the six values may be given. Two on an axis determine both
 * position and extent between them; one plus an explicit extent pins that edge;
 * an extent alone leaves the child at the start of the axis; nothing at all
 * lets the child shrink-wrap where it stands. That is enough for the cases
 * absolute positioning actually exists for — a badge in a corner, a scrubber
 * spanning the full width at the bottom — without a second layout model.
 *
 * Outside a Stack this is an ordinary wrapper honouring whatever bounds it was
 * given, because the anchors have nothing to be relative to.
 */
final class Positioned extends SingleChildNode
{
    protected ?int $preferred_width;

    protected ?int $preferred_height;

    public function __construct(
        protected ?int $left = null,
        protected ?int $top = null,
        protected ?int $right = null,
        protected ?int $bottom = null,
        ?int $width = null,
        ?int $height = null,
        ?Node $child = null,
    ) {
        parent::__construct($child);

        $this->preferred_width = $width;
        $this->preferred_height = $height;
    }

    /**
     * Stretch the child across every edge of the stack, inset by $inset.
     */
    public static function fill(int $inset = 0, ?Node $child = null): self
    {
        return new self($inset, $inset, $inset, $inset, child: $child);
    }

    public function anchors(
        ?int $left = null,
        ?int $top = null,
        ?int $right = null,
        ?int $bottom = null,
        ?int $width = null,
        ?int $height = null,
    ): static {
        $this->left = $left;
        $this->top = $top;
        $this->right = $right;
        $this->bottom = $bottom;
        $this->preferred_width = $width;
        $this->preferred_height = $height;

        return $this->markNeedsLayout();
    }

    /**
     * Resolve against the stack's own box and take up the resulting rectangle.
     *
     * Called by {@see Stack} once it knows how big it is, which is why this is a
     * second entry point rather than something {@see measure()} could do on its
     * own — the anchors are meaningless until the container has a size.
     */
    public function layoutIn(Rect $area): void
    {
        $child = $this->child();

        $intrinsic = is_null($child)
            ? Size::zero()
            : $child->layout(Constraints::loose(new Size($area->width, $area->height)));

        [$x, $width] = $this->resolveAxis($this->left, $this->right, $this->preferred_width, $area->width, $intrinsic->width);
        [$y, $height] = $this->resolveAxis($this->top, $this->bottom, $this->preferred_height, $area->height, $intrinsic->height);

        $this->layout(Constraints::tight(new Size($width, $height)), true);
        $this->placeAt($area->x + $x, $area->y + $y);
    }

    public function measure(Constraints $constraints): Size
    {
        $size = $constraints->constrain($this->size());
        $child = $this->child();

        if (! is_null($child)) {
            $child->layout(Constraints::tight($size));
            $child->placeAt(0, 0);
        }

        return $size;
    }

    /**
     * One axis of the anchor resolution, in container-local coordinates.
     *
     * @return array{0: int, 1: int} offset and extent
     */
    protected function resolveAxis(?int $start, ?int $end, ?int $extent, int $container, int $intrinsic): array
    {
        if (! is_null($start) && ! is_null($end)) {
            return [$start, max(0, $container - $start - $end)];
        }

        $extent ??= $intrinsic;

        if (! is_null($end)) {
            return [$container - $end - $extent, $extent];
        }

        return [$start ?? 0, $extent];
    }
}
