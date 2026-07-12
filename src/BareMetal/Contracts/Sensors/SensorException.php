<?php

namespace BareMetal\Contracts\Sensors;

use BareMetal\Contracts\Circuits\IntegratedCircuitException;

class SensorException extends IntegratedCircuitException
{
    public static function disabled(string $class): static
    {
        return new static("{$class} is disabled — call enable() before reading data.");
    }
}
