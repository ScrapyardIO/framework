<?php

namespace BareMetal\Contracts\Sensors\Distance;

use BareMetal\Contracts\Sensors\MeasuresData;

interface DistanceMeasurable extends MeasuresData
{
    public function distance(DistanceUnit $unit): float;
}
