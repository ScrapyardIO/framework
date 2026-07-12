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

    public static function buttonNotFound(string $label, string $class): static
    {
        return new static("Button [{$label}] was not found on [{$class}].");
    }

    public static function duplicateButtonLabel(string $label, string $class): static
    {
        return new static("Duplicate button label [{$label}] on [{$class}].");
    }

    public static function invalidButtonLayout(string $class): static
    {
        return new static("[{$class}] expects an iterable of BasicButton instances.");
    }
}
