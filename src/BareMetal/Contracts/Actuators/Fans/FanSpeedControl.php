<?php

namespace BareMetal\Contracts\Actuators\Fans;

interface FanSpeedControl extends BasicFanFunctionality
{
    /**
     * Get or set fan speed as a duty-cycle percentage (0-100).
     */
    public function speed(?int $percent = null): int;

    /**
     * Get or set the PWM carrier frequency in Hz.
     */
    public function frequency(?int $hz = null): int;
}
