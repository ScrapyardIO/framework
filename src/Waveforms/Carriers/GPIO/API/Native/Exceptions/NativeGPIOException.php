<?php

namespace Waveforms\Carriers\GPIO\API\Native\Exceptions;

use Waveforms\Carriers\GPIO\Exceptions\GPIOException;

class NativeGPIOException extends GPIOException
{
    public static function chipDoesNotExist(int $chip): static
    {
        return new static("/dev/gpiochip{$chip} does not exist on this device, or is busy.");
    }

    public static function couldNotOpenGPIOChip(int $chip): static
    {
        return new static("/dev/gpiochip{$chip} could not be opened.");
    }
}
