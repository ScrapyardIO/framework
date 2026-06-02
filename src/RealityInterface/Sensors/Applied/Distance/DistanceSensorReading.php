<?php

namespace RealityInterface\Sensors\Applied\Distance;

use RealityInterface\Sensors\Enums\LengthUnit;
use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\SensorEvent;

class DistanceSensorReading extends SensorEvent
{
    public function __construct(
        SensorType $sensor_type,
        public readonly int|float $value,
        public readonly LengthUnit $units,
        public readonly int $timestamp
    ) {
        parent::__construct(
            $sensor_type,
            SensorMeasurement::DISTANCE
        );
    }
}
