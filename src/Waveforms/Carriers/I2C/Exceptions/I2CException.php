<?php

namespace Waveforms\Carriers\I2C\Exceptions;

use RuntimeException;

class I2CException extends RuntimeException
{
    public static function missingSlaveAddress(): static
    {
        return new static('Missing slave address.');
    }

    public static function couldNotOpenI2CDevice(int $master): static
    {
        return new static("/dev/ioo2c-{$master} could not be opened.");
    }
}
