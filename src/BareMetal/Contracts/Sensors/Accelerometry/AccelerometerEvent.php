<?php

namespace BareMetal\Contracts\Sensors\Accelerometry;

use BareMetal\Contracts\Sensors\Distance\DistanceUnit;
use BareMetal\Contracts\Sensors\SensorEvent;

abstract class AccelerometerEvent extends SensorEvent
{
    public function __construct(

        int|float $timestamp
    ) {
        parent::__construct($timestamp);
    }
}
