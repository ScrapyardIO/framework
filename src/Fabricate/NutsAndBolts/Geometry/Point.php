<?php

namespace Fabricate\NutsAndBolts\Geometry;

/**
 * A position in integer device space.
 *
 * Sits alongside {@see Rect} so the UX layer can pass an origin around without
 * inventing a width and height it does not have.
 */
final readonly class Point
{
    public function __construct(
        public int $x,
        public int $y,
    ) {}

    public static function origin(): self
    {
        return new self(0, 0);
    }

    public function translate(int $dx, int $dy): self
    {
        return new self($this->x + $dx, $this->y + $dy);
    }

    public function equals(self $other): bool
    {
        return ($this->x === $other->x) && ($this->y === $other->y);
    }

    /**
     * The rectangle of $size anchored at this point.
     */
    public function withSize(Size $size): Rect
    {
        return new Rect($this->x, $this->y, $size->width, $size->height);
    }
}
