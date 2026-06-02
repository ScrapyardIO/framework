<?php

namespace RealityInterface\Sensors\Applied\Accelerometry;

use RealityInterface\Sensors\Enums\SensorMeasurement;
use RealityInterface\Sensors\Enums\SensorType;
use RealityInterface\Sensors\SensorEvent;

class VibrationReading extends SensorEvent
{
    /**
     * @param  float  $rms  AC-coupled RMS g across all three axes over the sample window.
     *                      Gravity (DC component) is subtracted before computing RMS,
     *                      so a perfectly still sensor returns ~0.0.
     * @param  int  $samples  Number of readings taken.
     */
    public function __construct(
        public readonly float $rms,
        public readonly int $samples,
        public readonly int $timestamp,
    ) {
        parent::__construct(SensorType::ACCELEROMETER, SensorMeasurement::VIBRATION);
    }
}
