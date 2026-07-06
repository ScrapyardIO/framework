<?php

namespace GPIO\Contracts\SPI;

use GPIO\Contracts\Common\GPIOException;

class SPIException extends GPIOException
{
    public static function missingMasterDevice(): static
    {
        return new static("SPI Master device is missing.");
    }
}
