<?php

namespace GPIO\Contracts\UART;

use GPIO\Contracts\Common\GPIOException;

class UARTException extends GPIOException
{
    public static function missingMasterDevice(): static
    {
        return new static("UART Port device is missing.");
    }

    public static function couldNotOpenUARTPort(string $path): static
    {
        return new static("{$path} could not be opened.");
    }
}
