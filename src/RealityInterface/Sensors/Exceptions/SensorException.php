<?php

namespace RealityInterface\Sensors\Exceptions;

use RealityInterface\Sensors\Enums\SensorType;
use RuntimeException;

class SensorException extends RuntimeException
{
    public static function missingRequiredAbility(string $class, string $circuit_class, string $attr): static
    {
        return new static("{$class} requires {$circuit_class} to have the {$attr} attribute.");
    }

    public static function sensorTypeNotFound(string $circuit_class, string $enum): static
    {
        return new static("{$circuit_class} missing {$enum}.");
    }

    public static function incorrectSensorType(string $circuit_class, SensorType $enum): static
    {
        return new static("{$circuit_class} needs to be {$enum->name}.");
    }
}
