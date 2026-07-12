<?php

namespace BareMetal\Contracts\Sensors\Speed;

interface Tachometer
{
    public function rpm(int $sample_ms = 500, int $pulses_per_revolution = 2): float;
}
