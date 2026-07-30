<?php

namespace DeptOfScrapyardRobotics\Tests\Actuation;

use Fabricate\Actuation\Servos\ContinuousServo;
use Fabricate\Actuation\Servos\PositionalServo;
use Fabricate\Contracts\Actuation\Interfaces\ContinuousServo as ContinuousServoContract;
use Fabricate\Contracts\Actuation\Interfaces\PositionalServo as PositionalServoContract;
use PHPUnit\Framework\TestCase;

class ServoComponentTest extends TestCase
{
    public function testPositionalServoForwardsCalibrationAndMotion(): void
    {
        $driver = new FakeContinuousServo;
        $servo = new PositionalServo($driver);

        $servo->to(45, 100, 5);
        $servo->pulse(1_200_000);
        $returned = $servo->calibrate(900, 2100, 1500);
        $servo->sweep(10, 170, [20, 160], 500, 5);

        $this->assertInstanceOf(PositionalServoContract::class, $servo);
        $this->assertSame($servo, $returned);
        $this->assertContains(['to', [45, 100, 5]], $driver->calls);
        $this->assertContains(['calibrate', [900, 2100, 1500]], $driver->calls);
        $this->assertContains(['sweep', [10, 170, [20, 160], 500, 5]], $driver->calls);
    }

    public function testContinuousServoForwardsDirectionAndDeadband(): void
    {
        $driver = new FakeContinuousServo;
        $servo = new ContinuousServo($driver);

        $servo->clockwise(80);
        $servo->counterClockwise(40);
        $servo->cw(70);
        $servo->ccw(30);
        $servo->stop();
        $returned = $servo->deadband(85, 95);

        $this->assertInstanceOf(ContinuousServoContract::class, $servo);
        $this->assertSame($servo, $returned);
        $this->assertContains(['clockwise', [80]], $driver->calls);
        $this->assertContains(['counterClockwise', [40]], $driver->calls);
        $this->assertContains(['deadband', [85, 95]], $driver->calls);
    }
}

class FakeContinuousServo implements ContinuousServoContract
{
    public array $calls = [];

    private int $pulse = 0;

    private int $position = 0;

    private bool $is_enabled = false;

    public function to(int $degrees, int $ms = 0, int $rate = 0): void
    {
        $this->position = $degrees;
        $this->calls[] = ['to', [$degrees, $ms, $rate]];
    }

    public function pulse(?int $ns = null): int
    {
        if (! is_null($ns)) {
            $this->pulse = $ns;
        }

        return $this->pulse;
    }

    public function calibrate(int $min, int $max, ?int $stop = null): static
    {
        $this->calls[] = ['calibrate', [$min, $max, $stop]];

        return $this;
    }

    public function center(int $ms = 0, int $rate = 0): void {}

    public function home(): void {}

    public function min(): void {}

    public function max(): void {}

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
        return $this->position;
    }

    public function enable(): void
    {
        $this->is_enabled = true;
    }

    public function disable(): void
    {
        $this->is_enabled = false;
    }

    public function enabled(): bool
    {
        return $this->is_enabled;
    }

    public function clockwise(int $speed = 100): void
    {
        $this->calls[] = ['clockwise', [$speed]];
    }

    public function counterClockwise(int $speed = 100): void
    {
        $this->calls[] = ['counterClockwise', [$speed]];
    }

    public function cw(int $speed = 100): void
    {
        $this->calls[] = ['cw', [$speed]];
    }

    public function ccw(int $speed = 100): void
    {
        $this->calls[] = ['ccw', [$speed]];
    }

    public function stop(): void
    {
        $this->calls[] = ['stop', []];
    }

    public function deadband(int $lower, int $upper): static
    {
        $this->calls[] = ['deadband', [$lower, $upper]];

        return $this;
    }

    public function close(): void {}
}
