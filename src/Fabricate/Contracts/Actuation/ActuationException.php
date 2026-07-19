<?php

namespace Fabricate\Contracts\Actuation;

use Fabricate\NutsAndBolts\ScrapyardIOException;

class ActuationException extends ScrapyardIOException
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
        return new static("[{$class}] expects an iterable of BasicInput instances.");
    }

    public static function unknownType(string $type): static
    {
        return new static("Unknown actuator type [{$type}].");
    }

    public static function typeComponentMismatch(
        string $type,
        string $expected_component,
        string $configured_component,
        string $circuit_slug,
    ): static {
        return new static(
            "Actuator type [{$type}] expects component [{$expected_component}], "
            ."but circuit [{$circuit_slug}] is configured for [{$configured_component}]."
        );
    }
}
