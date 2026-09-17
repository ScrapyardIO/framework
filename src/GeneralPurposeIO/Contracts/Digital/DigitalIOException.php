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

    public static function noDriverConfigured(): static
    {
        return new static('No DigitalIO connection driver is configured. Set gpio.protocols.digital-in.default to an installed adapter.');
    }
}
