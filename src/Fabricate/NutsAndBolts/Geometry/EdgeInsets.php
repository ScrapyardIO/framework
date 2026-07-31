<?php

namespace Fabricate\NutsAndBolts\Geometry;

/**
 * A per-edge inset, used for padding and for insetting a border.
 *
 * Insets may be negative, which grows rather than shrinks — that is what makes
 * a single {@see deflate()} usable for both an outline that must sit outside a
 * node's bounds and padding that must sit inside them.
 */
final readonly class EdgeInsets
{
    public function __construct(
        public int $left = 0,
        public int $top = 0,
        public int $right = 0,
        public int $bottom = 0,
    ) {}

    public static function zero(): self
    {
        return new self;
    }

    public static function all(int $inset): self
    {
        return new self($inset, $inset, $inset, $inset);
    }

    public static function symmetric(int $horizontal = 0, int $vertical = 0): self
    {
        return new self($horizontal, $vertical, $horizontal, $vertical);
    }

    public function horizontal(): int
    {
        return $this->left + $this->right;
    }

    public function vertical(): int
    {
        return $this->top + $this->bottom;
    }

    public function isZero(): bool
    {
        return ($this->left === 0)
            && ($this->top === 0)
            && ($this->right === 0)
            && ($this->bottom === 0);
    }

    /**
     * Shrink $rect inwards. Collapses to {@see Rect::empty()} rather than
     * inverting when the insets exceed the rect, so callers can branch on
     * isEmpty() instead of guarding against negative extents.
     */
    public function deflate(Rect $rect): Rect
    {
        $width = $rect->width - $this->horizontal();
        $height = $rect->height - $this->vertical();

        if (($width <= 0) || ($height <= 0)) {
            return Rect::empty();
        }

        return new Rect($rect->x + $this->left, $rect->y + $this->top, $width, $height);
    }

    /**
     * Grow $rect outwards, the inverse of {@see deflate()}.
     */
    public function inflate(Rect $rect): Rect
    {
        return new Rect(
            $rect->x - $this->left,
            $rect->y - $this->top,
            $rect->width + $this->horizontal(),
            $rect->height + $this->vertical(),
        );
    }

    public function equals(self $other): bool
    {
        return ($this->left === $other->left)
            && ($this->top === $other->top)
            && ($this->right === $other->right)
            && ($this->bottom === $other->bottom);
    }
}
