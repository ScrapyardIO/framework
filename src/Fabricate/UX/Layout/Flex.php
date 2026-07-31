<?php

namespace Fabricate\UX\Layout;

use Fabricate\Contracts\UX\Enums\Axis;
use Fabricate\Contracts\UX\Enums\CrossAxisAlignment;
use Fabricate\Contracts\UX\Enums\MainAxisAlignment;
use Fabricate\Contracts\UX\Enums\MainAxisSize;
use Fabricate\Contracts\UX\Flexible;
use Fabricate\NutsAndBolts\Geometry\Constraints;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\UX\Node;

/**
 * Lays children out in a line, the shared engine behind {@see Row} and
 * {@see Column}.
 *
 * Flex-lite: one pass down and back, no constraint solver. Inflexible children
 * are measured first against whatever main-axis room is left at the time, and
 * the remainder is split between the {@see Flexible} ones by weight. Measuring
 * inflexible children against a shrinking budget rather than an unbounded axis
 * is deliberate — on a 128px OLED a container that lets its children overflow
 * and then clips them is useless, so the last child gets squeezed instead.
 *
 * With an unbounded main axis there is no remainder to hand out, so flexible
 * children are measured loosely and simply shrink-wrap.
 */
abstract class Flex extends LayoutNode
{
    public function __construct(
        protected readonly Axis $axis,
        protected MainAxisAlignment $main_axis = MainAxisAlignment::START,
        protected CrossAxisAlignment $cross_axis = CrossAxisAlignment::START,
        protected int $gap = 0,
        protected MainAxisSize $main_axis_size = MainAxisSize::MAX,
    ) {
        parent::__construct();
    }

    public function axis(): Axis
    {
        return $this->axis;
    }

    public function mainAxis(MainAxisAlignment $alignment): static
    {
        if ($this->main_axis === $alignment) {
            return $this;
        }

        $this->main_axis = $alignment;

        return $this->markNeedsLayout();
    }

    /**
     * Whether to fill the offered main axis or wrap the children.
     *
     * MIN is what a card in a column wants: the default MAX claims the whole
     * remaining axis, which leaves nothing for the siblings that follow.
     */
    public function mainAxisSize(MainAxisSize $size): static
    {
        if ($this->main_axis_size === $size) {
            return $this;
        }

        $this->main_axis_size = $size;

        return $this->markNeedsLayout();
    }

    /**
     * Wrap the children rather than filling the offer, the common case for
     * {@see mainAxisSize()}.
     */
    public function wrapContent(): static
    {
        return $this->mainAxisSize(MainAxisSize::MIN);
    }

    public function crossAxis(CrossAxisAlignment $alignment): static
    {
        if ($this->cross_axis === $alignment) {
            return $this;
        }

        $this->cross_axis = $alignment;

        return $this->markNeedsLayout();
    }

    /**
     * Fixed spacing inserted between children, on top of whatever the main-axis
     * alignment distributes.
     */
    public function gap(int $gap): static
    {
        $gap = max(0, $gap);

        if ($this->gap === $gap) {
            return $this;
        }

        $this->gap = $gap;

        return $this->markNeedsLayout();
    }

    public function measure(Constraints $constraints): Size
    {
        $children = $this->participants();

        if ($children === []) {
            return $constraints->constrain(Size::zero());
        }

        $max_main = $this->boundedMain($constraints);
        $max_cross = $this->boundedCross($constraints);

        $gaps = $this->gap * (count($children) - 1);

        [$sizes, $used, $flex_total] = $this->measureInflexible($children, $max_main, $max_cross, $gaps);

        $sizes = $this->measureFlexible($children, $sizes, $max_main, $max_cross, $used, $flex_total);

        $used = $gaps;
        $cross_extent = 0;

        foreach ($sizes as $size) {
            $used += $this->mainOf($size);
            $cross_extent = max($cross_extent, $this->crossOf($size));
        }

        $size = $constraints->constrain($this->sizeOf(
            (is_null($max_main) || ! $this->main_axis_size->fillsAvailable()) ? $used : $max_main,
            ($this->cross_axis->resizesChild() && ! is_null($max_cross)) ? $max_cross : $cross_extent,
        ));

        $this->position($children, $sizes, $size, $used);

        return $size;
    }

    /**
     * Pass one: every child that is not flexible, each measured against the room
     * still unclaimed at the point it is reached.
     *
     * @param  array<int, Node>  $children
     * @return array{0: array<int, Size>, 1: int, 2: int}
     */
    protected function measureInflexible(array $children, ?int $max_main, ?int $max_cross, int $gaps): array
    {
        $sizes = [];
        $used = $gaps;
        $flex_total = 0;

        foreach ($children as $index => $child) {
            $flex = $this->flexOf($child);

            if ($flex > 0) {
                $flex_total += $flex;

                continue;
            }

            $remaining = is_null($max_main) ? null : max(0, $max_main - $used);

            $sizes[$index] = $child->layout($this->childConstraints(0, $remaining, $max_cross));
            $used += $this->mainOf($sizes[$index]);
        }

        return [$sizes, $used, $flex_total];
    }

    /**
     * Pass two: split what is left between the flexible children by weight.
     *
     * The remainder of the division is handed out one pixel at a time to the
     * earliest children, so the shares add up to exactly the space available
     * instead of leaving a stripe of background at the end of the row.
     *
     * @param  array<int, Node>  $children
     * @param  array<int, Size>  $sizes
     * @return array<int, Size>
     */
    protected function measureFlexible(
        array $children,
        array $sizes,
        ?int $max_main,
        ?int $max_cross,
        int $used,
        int $flex_total,
    ): array {
        if ($flex_total === 0) {
            return $sizes;
        }

        $free = is_null($max_main) ? 0 : max(0, $max_main - $used);
        $granted = 0;
        $weighed = 0;

        foreach ($children as $index => $child) {
            $flex = $this->flexOf($child);

            if ($flex === 0) {
                continue;
            }

            $weighed += $flex;

            // Accumulate against the running total rather than dividing each
            // share independently, which is what makes the pixels lost to
            // integer division reappear instead of vanishing.
            $share = intdiv($free * $weighed, $flex_total) - $granted;
            $granted += $share;

            $sizes[$index] = is_null($max_main)
                ? $child->layout($this->childConstraints(0, null, $max_cross))
                : $child->layout($this->childConstraints($share, $share, $max_cross));
        }

        ksort($sizes);

        return $sizes;
    }

    /**
     * Place each child along the main axis according to the alignment, and
     * across it according to the cross alignment.
     *
     * @param  array<int, Node>  $children
     * @param  array<int, Size>  $sizes
     */
    protected function position(array $children, array $sizes, Size $size, int $used): void
    {
        $count = count($children);
        $free = max(0, $this->mainOf($size) - $used);
        $cross_extent = $this->crossOf($size);

        [$offset, $between] = $this->distribute($free, $count);

        foreach ($children as $index => $child) {
            $child_size = $sizes[$index] ?? $child->size();
            $cross_free = $cross_extent - $this->crossOf($child_size);

            $cross = match ($this->cross_axis) {
                CrossAxisAlignment::START, CrossAxisAlignment::STRETCH => 0,
                CrossAxisAlignment::CENTER => $this->centred($cross_free),
                CrossAxisAlignment::END => $cross_free,
            };

            if ($this->axis === Axis::HORIZONTAL) {
                $child->placeAt($offset, $cross);
            } else {
                $child->placeAt($cross, $offset);
            }

            $offset += $this->mainOf($child_size) + $between;
        }
    }

    /**
     * Where the first child starts and how much sits between each pair, given
     * the leftover main-axis space.
     *
     * @return array{0: int, 1: int}
     */
    protected function distribute(int $free, int $count): array
    {
        return match ($this->main_axis) {
            MainAxisAlignment::START => [0, $this->gap],
            MainAxisAlignment::CENTER => [$this->centred($free), $this->gap],
            MainAxisAlignment::END => [$free, $this->gap],
            MainAxisAlignment::SPACE_BETWEEN => ($count > 1)
                ? [0, $this->gap + intdiv($free, $count - 1)]
                : [0, $this->gap],
            MainAxisAlignment::SPACE_AROUND => [
                intdiv(intdiv($free, $count), 2),
                $this->gap + intdiv($free, $count),
            ],
            MainAxisAlignment::SPACE_EVENLY => [
                intdiv($free, $count + 1),
                $this->gap + intdiv($free, $count + 1),
            ],
        };
    }

    /**
     * Turn a main/cross pair into constraints in the container's own axis terms,
     * so none of the arithmetic above has to know which direction it runs in.
     *
     * A cross alignment of STRETCH is the one case that changes a child's size
     * rather than its position, and it does so here by making the cross axis
     * tight.
     */
    protected function childConstraints(int $main_min, ?int $main_max, ?int $cross_max): Constraints
    {
        $main_max ??= PHP_INT_MAX;
        $cross_min = ($this->cross_axis->resizesChild() && ! is_null($cross_max)) ? $cross_max : 0;
        $cross_max ??= PHP_INT_MAX;

        return ($this->axis === Axis::HORIZONTAL)
            ? new Constraints(min($main_min, $main_max), $main_max, $cross_min, $cross_max)
            : new Constraints($cross_min, $cross_max, min($main_min, $main_max), $main_max);
    }

    protected function flexOf(Node $child): int
    {
        return ($child instanceof Flexible) ? max(0, $child->flex()) : 0;
    }

    protected function mainOf(Size $size): int
    {
        return $this->axis->extentOf($size->width, $size->height);
    }

    protected function crossOf(Size $size): int
    {
        return $this->axis->cross()->extentOf($size->width, $size->height);
    }

    protected function sizeOf(int $main, int $cross): Size
    {
        return ($this->axis === Axis::HORIZONTAL) ? new Size($main, $cross) : new Size($cross, $main);
    }

    /**
     * Null when the container's main axis is unbounded, which is the signal that
     * there is no leftover space to distribute.
     */
    protected function boundedMain(Constraints $constraints): ?int
    {
        return ($this->axis === Axis::HORIZONTAL)
            ? ($constraints->hasBoundedWidth() ? $constraints->max_width : null)
            : ($constraints->hasBoundedHeight() ? $constraints->max_height : null);
    }

    protected function boundedCross(Constraints $constraints): ?int
    {
        return ($this->axis === Axis::HORIZONTAL)
            ? ($constraints->hasBoundedHeight() ? $constraints->max_height : null)
            : ($constraints->hasBoundedWidth() ? $constraints->max_width : null);
    }
}
