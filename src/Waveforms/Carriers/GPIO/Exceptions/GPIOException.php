<?php

namespace Waveforms\Carriers\GPIO\Exceptions;

use RuntimeException;

class GPIOException extends RuntimeException
{
    public static function wrongGPIOPinType(string $type, string $driver): static
    {
        return new static("GPIOPin type {$type} is not supported in driver {$driver}.");
    }

    public static function pinAliasNotFound(string $alias): static
    {
        return new static("GPIOPin alias {$alias} not found.");
    }
}
