<?php

namespace Fabricate\Contracts\Actuation\Interfaces;

use Fabricate\Contracts\Circuits\IntegratedCircuit;

/**
 * Fan actuator with optional PWM speed control.
 *
 * Speed is expressed as a duty-cycle percentage (0-100) and frequency in Hz.
 */
interface Fan extends IntegratedCircuit
{
    public function on(): void;

    public function off(): void;

    public function speed(?int $percent = null): int;

    public function frequency(?int $hz = null): int;
}
