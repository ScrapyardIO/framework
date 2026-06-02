<?php

namespace RealityInterface\Sensors\Applied\Accelerometry;

use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\SensorEvent;

class AccelerometerReading extends SensorEvent
{
    public function __construct(
        public readonly float $x,
        public readonly float $y,
        public readonly float $z,
        public readonly int $timestamp,
    ) {
        parent::__construct(
            SensorType::ACCELEROMETER,
            SensorMeasurement::ACCELERATION,
        );
    }
}
