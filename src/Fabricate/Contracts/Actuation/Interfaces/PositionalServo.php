<?php

namespace Fabricate\Contracts\Actuation\Interfaces;

use Fabricate\Contracts\Circuits\IntegratedCircuit;

/**
 * Position-controlled servo actuator.
 */
interface PositionalServo extends IntegratedCircuit
{
    public function to(int $degrees, int $ms = 0, int $rate = 0): void;

    public function pulse(?int $ns = null): int;

    public function calibrate(int $min, int $max, ?int $stop = null): static;

    public function center(int $ms = 0, int $rate = 0): void;

    public function home(): void;

    public function min(): void;

    public function max(): void;

    /**
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
