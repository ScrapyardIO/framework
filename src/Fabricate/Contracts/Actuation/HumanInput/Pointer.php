<?php

namespace Fabricate\Contracts\Actuation\HumanInput;

use Fabricate\Contracts\Actuation\Interfaces\ButtonPad;

/**
 * Pointer input retaining absolute position, motion, wheel, and button state.
 */
interface Pointer extends ButtonPad
{
    public function coordinateSpace(): CoordinateSpace;

    public function x(): float;

    public function y(): float;

    public function deltaX(): float;

    public function deltaY(): float;

    public function wheelX(): float;

    public function wheelY(): float;
}
