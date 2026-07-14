<?php

namespace BareMetal\Contracts\Sensors;

use BareMetal\Contracts\Circuits\IntegratedCircuitException;

class SensorException extends IntegratedCircuitException
{
    public static function disabled(string $class): static
    {
        return new static("{$class} is disabled — call enable() before reading data.");
    }

    public static function sensorTypeNotEnabled(string $type): static
    {
        return new static("{$type} sensors are not enabled.");
    }

    public static function sensorNotRegistered(string $type, string $name): static
    {
        return new static("{$type} sensors {$name} has not been registered.");
    }
}
