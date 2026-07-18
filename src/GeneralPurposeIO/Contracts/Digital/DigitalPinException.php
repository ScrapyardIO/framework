<?php

namespace GeneralPurposeIO\Contracts\Digital;

use Fabricate\NutsAndBolts\ScrapyardIOException;
use GeneralPurposeIO\Contracts\Common\GPIOException;

class DigitalPinException extends GPIOException
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
