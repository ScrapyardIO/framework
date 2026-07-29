<?php

namespace Fabricate\Contracts\Sensors\Interfaces;

use Fabricate\Contracts\Circuits\IntegratedCircuit;
use Fabricate\Contracts\Sensors\Enums\HumidityUnit;

/**
 * Relative-humidity capable integrated circuit (AHT, etc.).
 *
 * Drivers report a fresh sample converted into the requested unit.
 * Multi-capability chips implement this alongside Thermometer when both apply.
 */
interface Hygrometer extends IntegratedCircuit
{
    /**
     * Measure relative humidity in the requested unit (default: percent).
     */
    public function measureHumidity(HumidityUnit $unit = HumidityUnit::PERCENT): float;
}
