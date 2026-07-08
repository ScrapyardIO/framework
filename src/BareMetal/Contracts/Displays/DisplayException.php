<?php

namespace BareMetal\Contracts\Displays;

use BareMetal\Contracts\Circuits\IntegratedCircuitException;

class DisplayException extends IntegratedCircuitException
{
    public static function invalidRegisterValue(string $field, int $value, int $min, int $max): static
    {
        return new static("Valid $field values are between $min and $max, you input $value.");
    }
}
