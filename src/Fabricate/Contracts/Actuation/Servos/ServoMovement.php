<?php

namespace Fabricate\Contracts\Actuation\Servos;

use Fabricate\Contracts\Actuation\ActuationActivity;

class ServoMovement extends ActuationActivity
{
    public function __construct(
        public readonly float $at,
        public readonly int $degrees,
        public readonly int $pulse_ns,
    ) {}
}
