<?php

namespace BareMetal\Actuation\Fans;

use BareMetal\Actuation\ActuationComponent;
use BareMetal\Contracts\Sensors\Speed\MeasuresRPM as MeasuresRPMInterface;

/**
 * Reality-facing tachometer wrapper beside the fan components — case fans
 * usually ship with a tach line co-resident on the same assembly.
 */
class TachometerComponent extends ActuationComponent
{
    public function __construct(
        protected MeasuresRPMInterface $tachometer,
    ) {}

    public function rpm(int $sample_ms = 500, int $pulses_per_revolution = 2): float
    {
        return $this->tachometer->rpm($sample_ms, $pulses_per_revolution);
    }
}
