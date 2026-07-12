<?php

namespace BareMetal\Contracts\Sensors\Accelerometry;

enum CelestialBody: string
{
    case SOL = 'sun';
    case MERCURY = 'mercury';
    case VENUS = 'venus';
    case TERRA = 'earth';
    case LUNA = 'moon';
    case MARS = 'mars';
    case CERES = 'ceres';
    case JUPITER = 'jupiter';
    case IO = 'io';
    case EUROPA = 'europa';
    case GANYMEDE = 'ganymede';
    case CALLISTO = 'callisto';
    case SATURN = 'saturn';
    case TITAN = 'titan';
    case URANUS = 'uranus';
    case TITANIA = 'titania';
    case OBERON = 'oberon';
    case NEPTUNE = 'neptune';
    case TRITON = 'triton';
    case PLUTO = 'pluto';
    case ERIS = 'eris';

    public function gravity(): float
    {
        return match ($this) {
            self::SOL => 274.1,
            self::MERCURY => 3.703,
            self::VENUS => 8.872,
            self::TERRA => 9.8067,
            self::LUNA => 1.625,
            self::MARS => 3.728,
            self::CERES => 0.28,
            self::JUPITER => 25.93,
            self::IO => 1.789,
            self::EUROPA => 1.314,
            self::GANYMEDE => 1.426,
            self::CALLISTO => 1.24,
            self::SATURN => 11.19,
            self::TITAN => 1.3455,
            self::URANUS => 9.01,
            self::TITANIA => 0.379,
            self::OBERON => 0.347,
            self::NEPTUNE => 11.28,
            self::TRITON => 0.779,
            self::PLUTO => 0.610,
            self::ERIS => 0.8,
        };
    }
}
