<?php

namespace Waveforms\Carriers\SPI\Exceptions;

use RuntimeException;

class SPIException extends RuntimeException
{
    public static function couldNotOpenSPIDevice(string $path): static
    {
        return new static("{$path} could not be opened.");
    }
}
