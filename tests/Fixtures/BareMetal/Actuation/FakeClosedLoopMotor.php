<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Actuation;

use BareMetal\Contracts\Actuators\Servos\ClosedLoopMotor;

/**
 * Recording ClosedLoopMotor double for PositionalServo component tests.
 */
class FakeClosedLoopMotor implements ClosedLoopMotor
{
    /** @var list<array{0: string, 1: array<int|string|null>}> */
    public array $calls = [];

    public int $position = 0;

    public int $pulse_ns = 1_500_000;

    public bool $is_enabled = false;

    /** @var array{min: int, max: int, stop: ?int} */
    public array $calibration = ['min' => 1000, 'max' => 2000, 'stop' => null];

    public function to(int $degrees, int $ms = 0, int $rate = 0): void
    {
        $this->calls[] = ['to', [$degrees, $ms, $rate]];
        $this->position = $degrees;
    }

    public function pulse(?int $ns = null): int
    {
        $this->calls[] = ['pulse', [$ns]];

        if (! is_null($ns)) {
            $this->pulse_ns = $ns;
        }

        return $this->pulse_ns;
    }

    public function calibrate(int $min, int $max, ?int $stop = null): static
    {
        $this->calls[] = ['calibrate', [$min, $max, $stop]];
        $this->calibration = ['min' => $min, 'max' => $max, 'stop' => $stop];

        return $this;
    }

    public function center(int $ms = 0, int $rate = 0): void
    {
        $this->calls[] = ['center', [$ms, $rate]];
        $this->position = 90;
    }

    public function home(): void
    {
        $this->calls[] = ['home', []];
        $this->position = 0;
    }

    public function min(): void
    {
        $this->calls[] = ['min', []];
        $this->position = 0;
    }

    public function max(): void
    {
        $this->calls[] = ['max', []];
        $this->position = 180;
    }

    /**
     * @param  array{0?: int, 1?: int}  $range
     */
    public function sweep(
        int $low = 0,
        int $high = 180,
        array $range = [],
        int $interval_of_half_sweep = 1000,
        int $step_of_each_degree = 10,
    ): void {
        $this->calls[] = ['sweep', [$low, $high, $range, $interval_of_half_sweep, $step_of_each_degree]];
    }

    public function getPosition(): int
    {
        $this->calls[] = ['getPosition', []];

        return $this->position;
    }

    public function enable(): void
    {
        $this->calls[] = ['enable', []];
        $this->is_enabled = true;
    }

    public function disable(): void
    {
        $this->calls[] = ['disable', []];
        $this->is_enabled = false;
    }

    public function enabled(): bool
    {
        $this->calls[] = ['enabled', []];

        return $this->is_enabled;
    }
}
