<?php

namespace BareMetal\Contracts\Sensors\Distance;

use BareMetal\Contracts\Sensors\SensorEvent;

abstract class DistanceEvent extends SensorEvent
{
    public function __construct(
        protected readonly int|float $distance,
        protected readonly DistanceUnit $unit,
        int|float $timestamp
    ) {
        parent::__construct($timestamp);
    }
}
