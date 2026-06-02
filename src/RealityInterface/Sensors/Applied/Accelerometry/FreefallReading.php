<?php

namespace RealityInterface\Sensors\Applied\Accelerometry;

use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\SensorEvent;

class FreefallReading extends SensorEvent
{
    /**
     * @param  bool  $is_freefall  True when total magnitude is below $threshold.
     * @param  float  $magnitude  Measured total g-force vector length.
     * @param  float  $threshold  The g threshold used for freefall detection.
     * @param  float  $x  Source X reading in g.
     * @param  float  $y  Source Y reading in g.
     * @param  float  $z  Source Z reading in g.
     */
    public function __construct(
        public readonly bool $is_freefall,
        public readonly float $magnitude,
        public readonly float $threshold,
        public readonly float $x,
        public readonly float $y,
        public readonly float $z,
        public readonly int $timestamp,
    ) {
        parent::__construct(SensorType::ACCELEROMETER, SensorMeasurement::FREEFALL);
    }
}
