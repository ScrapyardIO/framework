<?php

namespace RealityInterface\Sensors\Enums;

/**
 * Driver-agnostic GPS fix quality, mirroring the NMEA GGA quality indicator.
 * Each GPS chip maps its vendor enum onto this so callers get one common type.
 */
enum GpsFixQuality: int
{
    case NO_FIX = 0;
    case GPS = 1;
    case DGPS = 2;
    case PPS = 3;
    case RTK = 4;
    case FLOAT_RTK = 5;
    case ESTIMATED = 6;
    case MANUAL = 7;
    case SIMULATION = 8;

    public function label(): string
    {
        return match ($this) {
            self::NO_FIX => 'no fix',
            self::GPS => 'GPS fix',
            self::DGPS => 'differential GPS fix',
            self::PPS => 'PPS fix',
            self::RTK => 'real-time kinematic',
            self::FLOAT_RTK => 'float RTK',
            self::ESTIMATED => 'estimated (dead reckoning)',
            self::MANUAL => 'manual input mode',
            self::SIMULATION => 'simulation mode',
        };
    }

    public function hasFix(): bool
    {
        return $this->value >= self::GPS->value;
    }
}
