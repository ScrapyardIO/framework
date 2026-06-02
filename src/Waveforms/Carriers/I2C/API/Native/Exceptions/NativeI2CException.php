<?php

namespace Waveforms\Carriers\I2C\API\Native\Exceptions;

use Waveforms\Carriers\I2C\Exceptions\I2CException;

class NativeI2CException extends I2CException
{
    public static function missingMaster(): static
    {
        return new static('Missing Master i2c device.');
    }
}
