<?php

namespace Fabricate\Contracts\UX\Enums;

/**
 * Which direction a flex container lays its children out along.
 */
enum Axis: string
{
    case HORIZONTAL = 'HORIZONTAL';
    case VERTICAL = 'VERTICAL';

    public function cross(): self
    {
        return ($this === self::HORIZONTAL) ? self::VERTICAL : self::HORIZONTAL;
    }

    /**
     * The extent of a size along this axis, so flex maths can stay
     * axis-agnostic instead of branching on direction everywhere.
     */
    public function extentOf(int $width, int $height): int
    {
        return ($this === self::HORIZONTAL) ? $width : $height;
    }
}
