<?php

namespace RealityInterface\Sensors\Applied\Accelerometry;

use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\SensorEvent;

class ActivityReading extends SensorEvent
{
    /**
     * @param  bool  $is_moving  True when the maximum delta between consecutive samples exceeds $threshold.
     * @param  float  $max_delta  Largest inter-sample delta magnitude observed across the window.
     * @param  float  $threshold  The g delta threshold used for motion detection.
     * @param  int  $samples  Number of readings taken.
     */
    public function __construct(
        public readonly bool $is_moving,
        public readonly float $max_delta,
        public readonly float $threshold,
        public readonly int $samples,
        public readonly int $timestamp,
    ) {
        parent::__construct(SensorType::ACCELEROMETER, SensorMeasurement::ACTIVITY);
    }
}
