<?php

namespace Fabricate\Contracts\Sensors\Interfaces;

use Fabricate\Contracts\Circuits\IntegratedCircuit;
use Fabricate\Contracts\Sensors\Enums\PressureUnit;

/**
 * Barometric-pressure capable integrated circuit (BMP, etc.).
 *
 * Drivers report a fresh sample converted into the requested unit.
 * Multi-capability chips (e.g. BMP280) implement this alongside Thermometer.
 */
interface Barometer extends IntegratedCircuit
{
    /**
     * Measure barometric pressure in the requested unit.
     */
    public function measurePressure(PressureUnit $unit): float;
}
