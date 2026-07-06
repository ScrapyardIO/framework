<?php

namespace GPIO\Contracts\I2C;



use GPIO\Contracts\Common\GPIOException;

class I2CException extends GPIOException
{
    public static function invalidSlaveAddress(int $address): static
    {
        return new static("Only valid address between 0x08 and 0x77 allowed. Requested: [{$address}].");
    }

    public static function missingMasterDevice(): static
    {
        return new static("I2C Master device is missing.");
    }

    public static function missingSlaveAddress(): static
    {
        return new static("Slave address is missing.");
    }
}
