<?php

namespace GeneralPurposeIO\Contracts\I2C;

use GeneralPurposeIO\Contracts\Common\GPIOException;

class I2CException extends GPIOException
{
    public static function invalidSlaveAddress(int $address): static
    {
        return new static("Only valid address between 0x08 and 0x77 allowed. Requested: [{$address}].");
    }

    public static function missingMasterDevice(): static
    {
        return new static('I2C Master device is missing.');
    }

    public static function missingSlaveAddress(): static
    {
        return new static('Slave address is missing.');
    }

    public static function couldNotOpenI2CDevice(int|string $master): static
    {
        return new static("/dev/i2c-{$master} could not be opened.");
    }

    public static function couldNotOpenMpsseContext(string $device, string $error): static
    {
        return new static("MPSSE I2C context for {$device} could not be opened. {$error}");
    }

    public static function missingGpioChipForDigitalPins(): static
    {
        return new static('digitalPins($chip) is required when bundling POSIX digital pins on an I2C bus.');
    }
}
