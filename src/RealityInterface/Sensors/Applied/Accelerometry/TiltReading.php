<?php

namespace RealityInterface\Sensors\Applied\Accelerometry;

use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\SensorEvent;

class TiltReading extends SensorEvent
{
    /**
     * @param  float  $roll  Rotation around the X axis in degrees (-180 to +180).
     * @param  float  $pitch  Rotation around the Y axis in degrees (-90 to +90).
     * @param  float  $x  Source X reading in g.
     * @param  float  $y  Source Y reading in g.
     * @param  float  $z  Source Z reading in g.
     */
    public function __construct(
        public readonly float $roll,
        public readonly float $pitch,
        public readonly float $x,
        public readonly float $y,
        public readonly float $z,
        public readonly int $timestamp,
    ) {
        parent::__construct(SensorType::ACCELEROMETER, SensorMeasurement::TILT);
    }
}
