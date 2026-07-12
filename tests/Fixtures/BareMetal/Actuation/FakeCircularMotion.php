<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Actuation;

use BareMetal\Contracts\Actuators\Servos\CircularMotion;

/**
 * Recording CircularMotion double for ContinuousServo component tests.
 */
class FakeCircularMotion extends FakeClosedLoopMotor implements CircularMotion
{
    /** @var array{lower: int, upper: int} */
    public array $deadband = ['lower' => 90, 'upper' => 90];

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
        $this->position = 90;
    }

    public function deadband(int $lower, int $upper): static
    {
        $this->calls[] = ['deadband', [$lower, $upper]];
        $this->deadband = ['lower' => $lower, 'upper' => $upper];

        return $this;
    }
}
