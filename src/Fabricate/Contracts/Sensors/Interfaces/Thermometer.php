<?php

namespace Fabricate\Contracts\Sensors\Interfaces;

use Fabricate\Contracts\Circuits\IntegratedCircuit;
use Fabricate\Contracts\Sensors\Enums\TemperatureUnit;

/**
 * Temperature-capable integrated circuit (AHT, BMP, etc.).
 *
 * Drivers report a fresh sample converted into the requested unit.
 * Multi-capability chips (e.g. BMP280) implement this alongside Barometer.
 */
interface Thermometer extends IntegratedCircuit
{
    /**
     * Measure ambient temperature in the requested unit.
     */
    public function measureTemp(TemperatureUnit $unit): float;
}
