<?php

namespace GeneralPurposeIO\Contracts\I2C;

use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOException;

class I2CException extends GPIOException
{
    public static function invalidSlaveAddress(int $address): static
    {
        return new static("Only valid address between 0x03 and 0x77 allowed. Requested: [{$address}].");
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

    public static function missingGpioChipForDigitalPins(): static
    {
        return new static('digitalPins($chip) is required when bundling POSIX digital pins on an I2C bus.');
    }

    public static function noDriverConfigured(): static
    {
        return new static('No I2C connection driver is configured. Set gpio.protocols.i2c.default to an installed adapter.');
    }

    public static function transportClosed(int $address): static
    {
        return new static(sprintf('I2C slave 0x%02X is closed.', $address));
    }

    public static function notAttached(int $address): static
    {
        return new static(sprintf('I2C slave 0x%02X was not handed out by a connection driver, so it cannot be offloaded.', $address));
    }

    public static function messageTooLong(int $length): static
    {
        return new static("{$length} bytes is longer than the 8192-byte I2C message limit.");
    }

    public static function offloadTargetUnsupported(string $target): static
    {
        return new static("MPSSE I2C transfers run on the device's own USB pump; they cannot go to the [{$target}] work target.");
    }
}
