<?php

namespace Fabricate\NutsAndBolts\Geometry;

/**
 * An axis-aligned rectangle in integer device space.
 *
 * Lives in NutsAndBolts rather than Rendering or Framebuffers because both of
 * those layers need it (clip regions and damage rects respectively) and neither
 * may depend on the other.
 *
 * Stored as origin plus extent, but {@see fromBounds()} and {@see right()} /
 * {@see bottom()} speak in *inclusive* bounds to match the
 * [left, top, right, bottom] representation the dirty-region machinery uses.
 */
final readonly class Rect
{
    public function __construct(
        public int $x,
        public int $y,
        public int $width,
        public int $height,
    ) {}

    /**
     * Build from inclusive bounds, the shape dirty regions are tracked in.
     */
    public static function fromBounds(int $left, int $top, int $right, int $bottom): self
    {
        return new self($left, $top, ($right - $left) + 1, ($bottom - $top) + 1);
    }

    /**
     * The canonical nothing-to-draw rectangle returned by a disjoint intersect.
     */
    public static function empty(): self
    {
        return new self(0, 0, 0, 0);
    }

    public function left(): int
    {
        return $this->x;
    }

    public function top(): int
    {
        return $this->y;
    }

    /**
     * Inclusive right edge: a 1px-wide rect at x=5 has right() === 5.
     */
    public function right(): int
    {
        return ($this->x + $this->width) - 1;
    }

    public function bottom(): int
    {
        return ($this->y + $this->height) - 1;
    }

    public function isEmpty(): bool
    {
        return ($this->width <= 0) || ($this->height <= 0);
    }

    public function area(): int
    {
        return $this->isEmpty() ? 0 : ($this->width * $this->height);
    }

    public function contains(int $x, int $y): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        return ($x >= $this->x)
            && ($y >= $this->y)
            && ($x <= $this->right())
            && ($y <= $this->bottom());
    }

    public function containsRect(self $other): bool
    {
        if ($this->isEmpty() || $other->isEmpty()) {
            return false;
        }

        return ($other->left() >= $this->left())
            && ($other->top() >= $this->top())
            && ($other->right() <= $this->right())
            && ($other->bottom() <= $this->bottom());
    }

    public function intersects(self $other): bool
    {
        if ($this->isEmpty() || $other->isEmpty()) {
            return false;
        }

        return ($this->left() <= $other->right())
            && ($other->left() <= $this->right())
            && ($this->top() <= $other->bottom())
            && ($other->top() <= $this->bottom());
    }

    /**
     * Overlapping *or* merely edge-adjacent, the adjacency test dirty-region
     * coalescing uses so two abutting rects collapse into one transfer.
     */
    public function touches(self $other): bool
    {
        if ($this->isEmpty() || $other->isEmpty()) {
            return false;
        }

        return ($this->left() <= $other->right() + 1)
            && ($other->left() <= $this->right() + 1)
            && ($this->top() <= $other->bottom() + 1)
            && ($other->top() <= $this->bottom() + 1);
    }

    /**
     * Returns {@see empty()} when the two do not overlap, so callers can branch
     * on isEmpty() instead of handling null.
     */
    public function intersect(self $other): self
    {
        if (! $this->intersects($other)) {
            return self::empty();
        }

        return self::fromBounds(
            max($this->left(), $other->left()),
            max($this->top(), $other->top()),
            min($this->right(), $other->right()),
            min($this->bottom(), $other->bottom()),
        );
    }

    /**
     * The smallest rectangle covering both. An empty operand is ignored rather
     * than dragging the result back to the origin.
     */
    public function union(self $other): self
    {
        if ($this->isEmpty()) {
            return $other;
        }

        if ($other->isEmpty()) {
            return $this;
        }

        return self::fromBounds(
            min($this->left(), $other->left()),
            min($this->top(), $other->top()),
            max($this->right(), $other->right()),
            max($this->bottom(), $other->bottom()),
        );
    }

    public function translate(int $dx, int $dy): self
    {
        return new self($this->x + $dx, $this->y + $dy, $this->width, $this->height);
    }

    public function equals(self $other): bool
    {
        if ($this->isEmpty() && $other->isEmpty()) {
            return true;
        }

        return ($this->x === $other->x)
            && ($this->y === $other->y)
            && ($this->width === $other->width)
            && ($this->height === $other->height);
    }

    /**
     * Inclusive [left, top, right, bottom], for handing back to APIs that speak
     * bounds rather than origin/extent.
     *
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    public function toBounds(): array
    {
        return [$this->left(), $this->top(), $this->right(), $this->bottom()];
    }
}
