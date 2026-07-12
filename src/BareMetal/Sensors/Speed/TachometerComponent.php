<?php

namespace BareMetal\Sensors\Speed;

use BareMetal\Sensors\SensorComponent;
use BareMetal\Contracts\Sensors\Speed\Tachometer as TachometerContract;
use BareMetal\Contracts\Sensors\Speed\RPMReadings as RPMReadingsInterface;

/**
 * Reality-facing tachometer wrapper beside the fan components — case fans
 * usually ship with a tach line co-resident on the same assembly.
 */
class TachometerComponent extends SensorComponent implements TachometerContract
{
    public function __construct(
        protected RPMReadingsInterface $tachometer,
    ) {}

    public function rpm(int $sample_ms = 500, int $pulses_per_revolution = 2): float
    {
        return $this->tachometer->rpm($sample_ms, $pulses_per_revolution);
    }
}
