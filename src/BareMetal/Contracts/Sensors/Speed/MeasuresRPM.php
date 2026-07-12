<?php

namespace BareMetal\Contracts\Sensors\Speed;

use BareMetal\Contracts\Sensors\MeasuresData;

interface MeasuresRPM extends MeasuresData
{
    /**
     * Sample tach edges and return revolutions per minute.
     */
    public function rpm(int $sample_ms = 500, int $pulses_per_revolution = 2): float;
}
