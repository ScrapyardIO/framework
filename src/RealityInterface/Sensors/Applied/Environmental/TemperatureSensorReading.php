<?php

namespace RealityInterface\Sensors\Applied\Environmental;

use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\Enums\TemperatureUnit;
use RealityInterface\Sensors\SensorEvent;

class TemperatureSensorReading extends SensorEvent
{
    public function __construct(
        public readonly int|float $value,
        public readonly TemperatureUnit $units,
        public readonly int $timestamp
    ) {
        parent::__construct(
            SensorType::TEMPERATURE,
            SensorMeasurement::TEMPERATURE
        );
    }
}
