<?php

namespace Fabricate\Contracts\Sensors;

use Fabricate\Contracts\Sensors\Enums\TemperatureUnit;

interface TemperatureMeasurements
{
    public function measureTemp(TemperatureUnit $unit): float;
}
