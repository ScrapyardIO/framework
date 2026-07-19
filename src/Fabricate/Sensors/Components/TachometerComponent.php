<?php

namespace Fabricate\Sensors\Components;

use Fabricate\Contracts\Sensors\SensorException;
use Fabricate\Contracts\Sensors\Speed\RPMReadings as RPMReadingsInterface;
use Fabricate\Contracts\Sensors\Speed\Tachometer as TachometerContract;
use Fabricate\IntegratedCircuits\SensorIC;
use Fabricate\Sensors\SensorComponent;

class TachometerComponent extends SensorComponent implements TachometerContract
{
    public function __construct(
        protected RPMReadingsInterface $tachometer,
    ) {}

    public function rpm(int $sample_ms = 500, int $pulses_per_revolution = 2): float
    {
        return $this->tachometer->rpm($sample_ms, $pulses_per_revolution);
    }

    public static function buildWith(SensorIC $sensor): static
    {
        if ($sensor instanceof RPMReadingsInterface) {
            return new static($sensor);
        }

        throw new SensorException($sensor::class.' must implement RPMReadings');
    }
}
