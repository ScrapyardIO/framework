<?php

namespace BareMetal\Contracts\Actuators\HumanInput;

use BareMetal\Contracts\Actuators\ActuationComponent;

interface ButtonComponent extends ActuationComponent
{
    public function label(): string;

    public function button(): BasicButtonFunctionality;

    /**
     * Sample the wrapped IC and update edge / hold state.
     */
    public function poll(): static;

    /**
     * Continuous down state from the last poll().
     */
    public function isDown(): bool;

    /**
     * Rising edge on the last poll() — just went down.
     */
    public function isPressed(): bool;

    /**
     * Falling edge on the last poll() — just went up.
     */
    public function wasReleased(): bool;

    /**
     * Down continuously for at least the configured hold threshold.
     */
    public function isHolding(): bool;

    /**
     * Milliseconds this button has been held down (0 if up).
     */
    public function heldMs(): int;
}
