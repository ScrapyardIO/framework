<?php

namespace BareMetal\Contracts\Actuators;

use BareMetal\Contracts\Circuits\IntegratedCircuitException;

class ActuationException extends IntegratedCircuitException
{
    public static function tachometerNotAttached(string $class): static
    {
        return new static("No tachometer is attached to [{$class}].");
    }

    public static function invalidProperty(string $name, string $class): static
    {
        return new static("Invalid property [{$name}] on [{$class}]");
    }
}
