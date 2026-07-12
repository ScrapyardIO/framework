<?php

namespace BareMetal\Contracts\Actuators\Servos;

interface ClosedLoopMotor
{
    /**
     * Move to a position in degrees. Optional glide over $ms in $rate-degree steps.
     */
    public function to(int $degrees, int $ms = 0, int $rate = 0): void;

    /**
     * Get or set the raw pulse width in nanoseconds (PWM duty).
     */
    public function pulse(?int $ns = null): int;

    /**
     * Calibrate the pulse-width range in microseconds.
     */
    public function calibrate(int $min, int $max, ?int $stop = null): static;

    public function center(int $ms = 0, int $rate = 0): void;

    public function home(): void;

    public function min(): void;

    public function max(): void;

    /**
     * Sweep across a degree range (low → high → low), blocking.
     *
     * @param  array{0?: int, 1?: int}  $range
     */
    public function sweep(
        int $low = 0,
        int $high = 180,
        array $range = [],
        int $interval_of_half_sweep = 1000,
        int $step_of_each_degree = 10,
    ): void;

    public function getPosition(): int;

    public function enable(): void;

    public function disable(): void;

    public function enabled(): bool;
}
