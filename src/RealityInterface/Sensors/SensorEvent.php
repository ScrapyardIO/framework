<?php

namespace RealityInterface\Sensors;

use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;

class SensorEvent
{
    public function __construct(
        public readonly SensorType $sensor_type = SensorType::DUMMY,
        public readonly SensorMeasurement $measurement = SensorMeasurement::NO_OP,
    ) {}
}
