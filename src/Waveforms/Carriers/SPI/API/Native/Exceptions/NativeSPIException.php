<?php

namespace Waveforms\Carriers\SPI\API\Native\Exceptions;

use Waveforms\Carriers\SPI\Exceptions\SPIException;

class NativeSPIException extends SPIException
{
    public static function missingBus(): static
    {
        return new static('Missing SPI bus.');
    }
}
