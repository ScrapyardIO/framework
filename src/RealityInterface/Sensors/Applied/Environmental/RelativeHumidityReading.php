<?php

namespace RealityInterface\Sensors\Applied\Environmental;

use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\SensorEvent;

class RelativeHumidityReading extends SensorEvent
{
    public function __construct(
        public readonly int|float $percent,
        public readonly int $timestamp
    ) {
        parent::__construct(
            SensorType::RELATIVE_HUMIDITY,
            SensorMeasurement::RELATIVE_HUMIDITY
        );
    }
}
