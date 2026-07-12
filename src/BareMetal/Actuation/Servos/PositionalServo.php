<?php

namespace BareMetal\Actuation\Servos;

use BareMetal\Contracts\Actuators\Servos\ClosedLoopMotor;
use BareMetal\Contracts\Actuators\Servos\ClosedLoopMotor as ClosedLoopMotorInterface;

/**
 * End-developer facade for a positional (angle) hobby servo.
 *
 * App builds a ClosedLoopMotor IC (e.g. GenericPositionalServo::pwm($channel)),
 * then wraps it here the same way BasicFan / SpeedControllableFan wrap fan ICs.
 */
class PositionalServo extends ServoComponent implements ClosedLoopMotor
{
    public function __construct(
        ClosedLoopMotorInterface $servo,
    ) {
        parent::__construct($servo);
    }

    public function center(int $ms = 0, int $rate = 0): void
    {
        $this->actuator->center($ms, $rate);
    }

    public function home(): void
    {
        $this->actuator->home();
    }

    public function min(): void
    {
        $this->actuator->min();
    }

    public function max(): void
    {
        $this->actuator->max();
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
        $this->actuator->sweep($low, $high, $range, $interval_of_half_sweep, $step_of_each_degree);
    }
}
