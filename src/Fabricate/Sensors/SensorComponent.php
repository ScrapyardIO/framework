<?php

namespace Fabricate\Sensors;

use Fabricate\Contracts\Sensors\SensorComponent as ComponentContract;
use Fabricate\IntegratedCircuits\SensorIC;

abstract class SensorComponent implements ComponentContract
{
    abstract public static function buildWith(SensorIC $sensor): static;
}
