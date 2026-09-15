<?php

namespace GeneralPurposeIO\Contracts\Common;

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
}
