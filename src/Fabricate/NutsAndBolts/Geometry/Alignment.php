<?php

namespace Fabricate\NutsAndBolts\Geometry;

/**
 * Where a smaller box sits inside a larger one, as a fraction of the leftover
 * space on each axis.
 *
 * Fractions are thousandths rather than floats, because every coordinate in this
 * stack is an integer device pixel and carrying floats here would only invite
 * rounding drift between a measured position and a painted one.
 *
 * 0 is left/top, 500 is centre, 1000 is right/bottom.
 */
final readonly class Alignment
{
    public function __construct(
        public int $horizontal,
        public int $vertical,
    ) {}

    public static function topLeft(): self
    {
        return new self(0, 0);
    }

    public static function topCenter(): self
    {
        return new self(500, 0);
    }

    public static function topRight(): self
    {
        return new self(1000, 0);
    }

    public static function centerLeft(): self
    {
        return new self(0, 500);
    }

    public static function center(): self
    {
        return new self(500, 500);
    }

    public static function centerRight(): self
    {
        return new self(1000, 500);
    }

    public static function bottomLeft(): self
    {
        return new self(0, 1000);
    }

    public static function bottomCenter(): self
    {
        return new self(500, 1000);
    }

    public static function bottomRight(): self
    {
        return new self(1000, 1000);
    }

    /**
     * Place $child inside $container.
     *
     * A child larger than its container gets a negative offset, which is correct:
     * a centred oversized child should overhang evenly on both sides rather than
     * being pinned to the top-left.
     */
    public function positionIn(Rect $container, Size $child): Rect
    {
        $free_width = $container->width - $child->width;
        $free_height = $container->height - $child->height;

        return new Rect(
            $container->x + $this->scale($free_width, $this->horizontal),
            $container->y + $this->scale($free_height, $this->vertical),
            $child->width,
            $child->height,
        );
    }

    public function equals(self $other): bool
    {
        return ($this->horizontal === $other->horizontal) && ($this->vertical === $other->vertical);
    }

    /**
     * Rounds to nearest rather than truncating, so centring an odd leftover
     * biases consistently instead of always drifting toward the origin.
     *
     * Rounds away from zero on both signs, so an oversized child overhangs its
     * container evenly instead of being pulled back toward the origin.
     */
    protected function scale(int $free, int $fraction): int
    {
        $scaled = $free * $fraction;

        return intdiv($scaled + (($scaled >= 0) ? 500 : -500), 1000);
    }
}
