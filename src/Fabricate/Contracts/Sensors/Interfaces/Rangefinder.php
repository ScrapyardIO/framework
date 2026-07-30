<?php

namespace Fabricate\Contracts\Sensors\Interfaces;

use Fabricate\Contracts\Circuits\IntegratedCircuit;
use Fabricate\Contracts\Sensors\Enums\DistanceUnit;

/**
 * Distance / proximity capable integrated circuit (VL6180X, HC-SR04, etc.).
 *
 * Drivers report a fresh sample converted into the requested unit.
 */
interface Rangefinder extends IntegratedCircuit
{
    /**
     * Measure distance in the requested unit.
     */
    public function distance(DistanceUnit $unit): float;
}
