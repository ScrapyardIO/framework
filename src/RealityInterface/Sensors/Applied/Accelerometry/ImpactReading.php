<?php

namespace RealityInterface\Sensors\Applied\Accelerometry;

use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\SensorEvent;

class ImpactReading extends SensorEvent
{
    /**
     * @param  float  $peak_g  Highest total g-force magnitude observed across the sample window.
     * @param  float  $x  X reading at the moment of peak g.
     * @param  float  $y  Y reading at the moment of peak g.
     * @param  float  $z  Z reading at the moment of peak g.
     * @param  int  $samples  Number of readings taken.
     */
    public function __construct(
        public readonly float $peak_g,
        public readonly float $x,
        public readonly float $y,
        public readonly float $z,
        public readonly int $samples,
        public readonly int $timestamp,
    ) {
        parent::__construct(SensorType::ACCELEROMETER, SensorMeasurement::IMPACT);
    }
}
