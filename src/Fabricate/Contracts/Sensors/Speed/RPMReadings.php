<?php

namespace Fabricate\Contracts\Sensors\Speed;

interface RPMReadings
{
    public function rpm(int $sample_ms = 500, int $pulses_per_revolution = 2): float;
}
