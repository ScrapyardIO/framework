<?php

namespace Fabricate\NutsAndBolts\Geometry;

use InvalidArgumentException;

/**
 * The size range a parent will accept from a child during measurement.
 *
 * This is the whole of the layout protocol: a parent hands down a range, a child
 * answers with a {@see Size} inside it. There is no constraint solver and no
 * second pass, which is what keeps flex-lite layout a single walk down and back.
 *
 * A tight constraint (min equal to max) means the parent has already decided, and
 * is also what makes a node a layout boundary — its children cannot change its
 * size, so their resizing never disturbs its ancestors.
 */
final readonly class Constraints
{
    public function __construct(
        public int $min_width,
        public int $max_width,
        public int $min_height,
        public int $max_height,
    ) {
        if (($min_width > $max_width) || ($min_height > $max_height)) {
            throw new InvalidArgumentException(
                "Constraints minimum must not exceed maximum, got {$min_width}..{$max_width} by {$min_height}..{$max_height}."
            );
        }

        if (($min_width < 0) || ($min_height < 0)) {
            throw new InvalidArgumentException('Constraints minimum must not be negative.');
        }
    }

    /**
     * Exactly this size and nothing else.
     */
    public static function tight(Size $size): self
    {
        return new self($size->width, $size->width, $size->height, $size->height);
    }

    /**
     * Anything up to this size, which is what a container offers a child that is
     * free to be as small as it likes.
     */
    public static function loose(Size $size): self
    {
        return new self(0, $size->width, 0, $size->height);
    }

    public static function unbounded(): self
    {
        return new self(0, PHP_INT_MAX, 0, PHP_INT_MAX);
    }

    public function isTight(): bool
    {
        return ($this->min_width === $this->max_width) && ($this->min_height === $this->max_height);
    }

    public function hasBoundedWidth(): bool
    {
        return $this->max_width !== PHP_INT_MAX;
    }

    public function hasBoundedHeight(): bool
    {
        return $this->max_height !== PHP_INT_MAX;
    }

    /**
     * The largest size these constraints permit. Meaningless when unbounded, so
     * callers must check first.
     */
    public function biggest(): Size
    {
        return new Size($this->max_width, $this->max_height);
    }

    public function smallest(): Size
    {
        return new Size($this->min_width, $this->min_height);
    }

    /**
     * Clamp a desired size into range, which is how a parent enforces its offer
     * against a child that answered out of bounds.
     */
    public function constrain(Size $size): Size
    {
        return new Size(
            max($this->min_width, min($this->max_width, $size->width)),
            max($this->min_height, min($this->max_height, $size->height)),
        );
    }

    public function allows(Size $size): bool
    {
        return ($size->width >= $this->min_width)
            && ($size->width <= $this->max_width)
            && ($size->height >= $this->min_height)
            && ($size->height <= $this->max_height);
    }

    /**
     * Drop the minimums, keeping the ceiling. A container that has reserved space
     * for padding passes the remainder down loosely.
     */
    public function loosened(): self
    {
        return new self(0, $this->max_width, 0, $this->max_height);
    }

    /**
     * Shrink the ceiling by space the parent has already spent, never going
     * negative — padding wider than the offer collapses the child rather than
     * inverting the range.
     */
    public function deflate(EdgeInsets $insets): self
    {
        $width = $this->hasBoundedWidth() ? max(0, $this->max_width - $insets->horizontal()) : PHP_INT_MAX;
        $height = $this->hasBoundedHeight() ? max(0, $this->max_height - $insets->vertical()) : PHP_INT_MAX;

        return new self(
            min($this->min_width, $width),
            $width,
            min($this->min_height, $height),
            $height,
        );
    }

    public function equals(self $other): bool
    {
        return ($this->min_width === $other->min_width)
            && ($this->max_width === $other->max_width)
            && ($this->min_height === $other->min_height)
            && ($this->max_height === $other->max_height);
    }
}
