<?php

namespace GeneralPurposeIO\Contracts\Digital;

use GeneralPurposeIO\Contracts\Core\GPIOLevelException;

class DigitalIOException extends GPIOLevelException
{
    public static function missingDigitalPinDevice(): static
    {
        return new static("DigitalPin device is missing.");
    }

    public static function missingDigitalPinOffset(): static
    {
        return new static("DigitalPin offset is missing.");
    }
}
