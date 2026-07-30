<?php

namespace Fabricate\Actuation\Servos;

use Fabricate\Contracts\Actuation\Interfaces\ContinuousServo as ContinuousServoContract;

class ContinuousServo extends ServoComponent implements ContinuousServoContract
{
    public function __construct(ContinuousServoContract $servo)
    {
        parent::__construct($servo);
    }

    public function clockwise(int $speed = 100): void
    {
        $this->continuousServo()->clockwise($speed);
    }

    public function counterClockwise(int $speed = 100): void
    {
        $this->continuousServo()->counterClockwise($speed);
    }

    public function cw(int $speed = 100): void
    {
        $this->continuousServo()->cw($speed);
    }

    public function ccw(int $speed = 100): void
    {
        $this->continuousServo()->ccw($speed);
    }

    public function stop(): void
    {
        $this->continuousServo()->stop();
    }

    public function deadband(int $lower, int $upper): static
    {
        $this->continuousServo()->deadband($lower, $upper);

        return $this;
    }

    private function continuousServo(): ContinuousServoContract
    {
        /** @var ContinuousServoContract */
        return $this->servo;
    }
}
