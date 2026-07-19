<?php

namespace Fabricate\Actuation\Components;

use Fabricate\Contracts\Actuation\ActuationException;
use Fabricate\Contracts\Actuation\Servos\CircularMotion;
use Fabricate\Contracts\Actuation\Servos\CircularMotion as CircularMotionInterface;
use Fabricate\IntegratedCircuits\ActuatorIC;

class ContinuousServo extends Servo implements CircularMotion
{
    public function __construct(
        CircularMotionInterface $servo,
    ) {
        parent::__construct($servo);
    }

    public function clockwise(int $speed = 100): void
    {
        $this->servo()->clockwise($speed);
    }

    public function counterClockwise(int $speed = 100): void
    {
        $this->servo()->counterClockwise($speed);
    }

    public function cw(int $speed = 100): void
    {
        $this->servo()->cw($speed);
    }

    public function ccw(int $speed = 100): void
    {
        $this->servo()->ccw($speed);
    }

    public function stop(): void
    {
        $this->servo()->stop();
    }

    public function deadband(int $lower, int $upper): static
    {
        $this->servo()->deadband($lower, $upper);

        return $this;
    }

    public function center(int $ms = 0, int $rate = 0): void
    {
        $this->servo()->center($ms, $rate);
    }

    public function home(): void
    {
        $this->servo()->home();
    }

    public function min(): void
    {
        $this->servo()->min();
    }

    public function max(): void
    {
        $this->servo()->max();
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
        $this->servo()->sweep($low, $high, $range, $interval_of_half_sweep, $step_of_each_degree);
    }

    public static function buildWith(ActuatorIC $actuator): static
    {
        if ($actuator instanceof CircularMotionInterface) {
            return new static($actuator);
        }

        throw new ActuationException($actuator::class.' must implement CircularMotion');
    }

    private function servo(): CircularMotionInterface
    {
        /** @var CircularMotionInterface */
        return $this->actuator;
    }
}
