<?php

namespace Fabricate\NutsAndBolts\Geometry;

/**
 * An extent in integer device space.
 *
 * Layout needs to talk about how big something wants to be before it knows
 * where it will sit, which {@see Rect} cannot express on its own.
 *
 * A zero or negative extent is empty rather than invalid, matching
 * {@see Rect::isEmpty()} so a collapsed node is a normal state to pass around.
 */
final readonly class Size
{
    public function __construct(
        public int $width,
        public int $height,
    ) {}

    public static function zero(): self
    {
        return new self(0, 0);
    }

    public static function square(int $extent): self
    {
        return new self($extent, $extent);
    }

    public function isEmpty(): bool
    {
        return ($this->width <= 0) || ($this->height <= 0);
    }

    public function area(): int
    {
        return $this->isEmpty() ? 0 : ($this->width * $this->height);
    }

    /**
     * Shrink to fit inside $other on both axes, never growing.
     */
    public function constrainTo(self $other): self
    {
        return new self(
            min($this->width, $other->width),
            min($this->height, $other->height),
        );
    }

    public function grownBy(int $dw, int $dh): self
    {
        return new self($this->width + $dw, $this->height + $dh);
    }

    public function equals(self $other): bool
    {
        if ($this->isEmpty() && $other->isEmpty()) {
            return true;
        }

        return ($this->width === $other->width) && ($this->height === $other->height);
    }

    /**
     * This extent anchored at the origin.
     */
    public function atOrigin(): Rect
    {
        return new Rect(0, 0, $this->width, $this->height);
    }
}
