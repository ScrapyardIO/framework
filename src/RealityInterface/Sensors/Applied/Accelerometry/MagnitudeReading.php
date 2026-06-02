<?php

namespace RealityInterface\Sensors\Applied\Accelerometry;

use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\SensorEvent;

class MagnitudeReading extends SensorEvent
{
    /**
     * @param  float  $magnitude  Total g-force vector length √(x²+y²+z²). ~1.0 at rest.
     * @param  float  $x  Source X reading in g.
     * @param  float  $y  Source Y reading in g.
     * @param  float  $z  Source Z reading in g.
     */
    public function __construct(
        public readonly float $magnitude,
        public readonly float $x,
        public readonly float $y,
        public readonly float $z,
        public readonly int $timestamp,
    ) {
        parent::__construct(SensorType::ACCELEROMETER, SensorMeasurement::MAGNITUDE);
    }
}
