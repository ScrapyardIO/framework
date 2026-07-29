<?php

namespace Fabricate\Contracts\Sensors\Enums;

enum PressureUnit: string
{
    case PASCAL = 'Pa';
    case HECTOPASCAL = 'hPa';
    case MILLIBAR = 'mbar';
    case INHG = 'inHg';

    /**
     * Convert $value from this unit into $to.
     */
    public function convert(float $value, PressureUnit $to): float
    {
        if ($this === $to) {
            return $value;
        }

        $pascals = match ($this) {
            self::PASCAL => $value,
            self::HECTOPASCAL, self::MILLIBAR => $value * 100.0,
            self::INHG => $value * 3386.389,
        };

        return match ($to) {
            self::PASCAL => $pascals,
            self::HECTOPASCAL, self::MILLIBAR => $pascals / 100.0,
            self::INHG => $pascals / 3386.389,
        };
    }
}
