<?php

namespace Fabricate\Contracts\Sensors;

use Fabricate\NutsAndBolts\ScrapyardIOException;

class SensorException extends ScrapyardIOException
{
    public static function disabled(string $class): static
    {
        return new static("{$class} is disabled — call enable() before reading data.");
    }
}