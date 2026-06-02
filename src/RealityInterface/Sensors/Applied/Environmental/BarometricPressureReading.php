<?php

namespace RealityInterface\Sensors\Applied\Environmental;

use RealityInterface\Sensors\Enums\PressureUnit;
use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\SensorEvent;

class BarometricPressureReading extends SensorEvent
{
    public function __construct(
        public readonly int|float $value,
        public readonly PressureUnit $units,
        public readonly int $timestamp
    ) {
        parent::__construct(
            SensorType::BAROMETER,
            SensorMeasurement::BAROMETRIC_PRESSURE
        );
    }
}
