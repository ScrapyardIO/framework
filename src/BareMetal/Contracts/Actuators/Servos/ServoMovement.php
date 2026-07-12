<?php

namespace BareMetal\Contracts\Actuators\Servos;

use BareMetal\Contracts\Actuators\ActuationActivity;

class ServoMovement extends ActuationActivity
{
    public function __construct(
        public readonly float $at,
        public readonly int $degrees,
        public readonly int $pulse_ns,
    ) {}
}
