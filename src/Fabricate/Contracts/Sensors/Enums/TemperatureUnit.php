<?php

namespace Fabricate\Contracts\Sensors\Enums;

enum TemperatureUnit: string
{
    case CELSIUS = 'C';
    case FAHRENHEIT = 'F';

    /**
     * Convert $value from this unit into $to.
     */
    public function convert(float $value, TemperatureUnit $to): float
    {
        if ($this === $to) {
            return $value;
        }

        $celsius = match ($this) {
            self::CELSIUS => $value,
            self::FAHRENHEIT => ($value - 32.0) * 5.0 / 9.0,
        };

        return match ($to) {
            self::CELSIUS => $celsius,
            self::FAHRENHEIT => ($celsius * 9.0 / 5.0) + 32.0,
        };
    }
}
