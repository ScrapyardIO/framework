<?php

namespace Waveforms\Carriers\UART\Exceptions;

use RuntimeException;

class UARTException extends RuntimeException
{
    public static function couldNotOpenUARTPort(string $path): static
    {
        return new static("{$path} could not be opened.");
    }
}
