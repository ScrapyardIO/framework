<?php

namespace BareMetal\Actuation\HumanInput;

use BareMetal\Contracts\Actuators\HumanInput\BasicButtonFunctionality;

/**
 * Multiplexed / banked digital input — latch a snapshot bit, then read isDown().
 * Used by I2C pads (Wii Classic, Nunchuck) so one poll feeds many BasicButtons.
 */
class LatchedButton implements BasicButtonFunctionality
{
    public function __construct(
        protected bool $down = false,
    ) {}

    public function latch(bool $down): void
    {
        $this->down = $down;
    }

    public function isDown(): bool
    {
        return $this->down;
    }
}
