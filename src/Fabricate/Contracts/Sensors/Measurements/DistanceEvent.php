<?php

namespace Fabricate\Contracts\Sensors\Measurements;



use Fabricate\Contracts\Sensors\Enums\DistanceUnit;

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
