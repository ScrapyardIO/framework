<?php

namespace Fabricate\Contracts\Sensors\Enums;

enum HumidityUnit: string
{
    /**
     * Relative humidity as a percentage (0–100).
     */
    case PERCENT = '%';

    /**
     * Convert $value from this unit into $to.
     *
     * Relative humidity percent is currently the only supported unit; the
     * conversion hook exists so absolute-humidity units can land later
     * without changing Hygrometer circuit signatures.
     */
    public function convert(float $value, HumidityUnit $to): float
    {
        if ($this === $to) {
            return $value;
        }

        // Only PERCENT exists today — identity until additional units ship.
        return $value;
    }
}
