<?php

namespace BareMetal\Contracts\Sensors\Distance;

use BareMetal\Contracts\Sensors\SensorComponent;

interface DistanceSensor extends SensorComponent
{
    public function distance(DistanceUnit $unit) : float;

    public function within(array $range, DistanceUnit $unit, callable $callback): void;

    public function measure(int $num_readings = 1, DistanceUnit $unit = DistanceUnit::MM): ?DistanceEvent;
}
