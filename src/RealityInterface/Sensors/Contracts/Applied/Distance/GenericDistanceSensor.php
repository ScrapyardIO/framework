<?php

namespace RealityInterface\Sensors\Contracts\Applied\Distance;

use RealityInterface\Sensors\Enums\LengthUnit;

interface GenericDistanceSensor
{
    public function getDistance(LengthUnit $unit = LengthUnit::CM): int|float;
}
