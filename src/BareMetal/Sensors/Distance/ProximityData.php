<?php

namespace BareMetal\Sensors\Distance;

use BareMetal\Contracts\Sensors\Distance\DistanceEvent;
use BareMetal\Contracts\Sensors\Distance\DistanceUnit;

class ProximityData extends DistanceEvent
{
    public function __construct(
        int|float $value,
        DistanceUnit $unit,
        int|float $timestamp
    ) {
        parent::__construct($value, $unit, $timestamp);
    }
}
