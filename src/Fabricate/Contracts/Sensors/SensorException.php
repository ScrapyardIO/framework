<?php

namespace Fabricate\Contracts\Sensors;

use Fabricate\NutsAndBolts\ScrapyardIOException;

class SensorException extends ScrapyardIOException
{
    public static function disabled(string $class): static
    {
        return new static("{$class} is disabled — call enable() before reading data.");
    }

    public static function unknownType(string $type): static
    {
        return new static("Unknown sensor type [{$type}].");
    }

    public static function typeComponentMismatch(
        string $type,
        string $expected_component,
        string $configured_component,
        string $circuit_slug,
    ): static {
        return new static(
            "Sensor type [{$type}] expects component [{$expected_component}], "
            ."but circuit [{$circuit_slug}] is configured for [{$configured_component}]."
        );
    }
}
