<?php

namespace GeneralPurposeIO\Contracts\NutsAndBolts;

use GeneralPurposeIO\Contracts\Core\GPIOLevelException;

class GPIOException extends GPIOLevelException
{
    public static function carrierFactoryNotImplemented(string $class_name): static
    {
        return new static("{$class_name} required a CarrierFactory Attribute.");
    }

    public static function unsupportedDriverProtocol(string $protocol, string $library): static
    {
        return new static("{$library} does not support {$protocol}.");
    }

    public static function transferInFlight(string $name): static
    {
        return new static("Transfer [{$name}] is already in flight on the gpio resource.");
    }

    public static function invalidDeferBudget(int $budget): static
    {
        return new static("gpio.io_pools.defer_per_tick must be null or at least 1; got [{$budget}].");
    }

    public static function unknownProtocol(string $name): static
    {
        return new static("GPIO protocol [{$name}] is not registered.");
    }

    public static function recurrenceInFlight(string $name): static
    {
        return new static("Recurrence [{$name}] is already recurring on the gpio resource; stop() it first.");
    }

    public static function invalidCadence(int $ticks): static
    {
        return new static("A recurrence runs every N ticks with N at least 1; got [{$ticks}].");
    }

    public static function streamInFlight(string $name): static
    {
        return new static("Stream [{$name}] is already streaming on the gpio resource.");
    }

    public static function invalidChunk(int $chunk): static
    {
        return new static("A stream sends a chunk of at least 1 byte per tick; got [{$chunk}].");
    }
}
