<?php

namespace Fabricate\Actuation\Components;

use Fabricate\Actuation\ActuationComponent;
use Fabricate\Contracts\Actuation\ActuationException;
use Fabricate\Contracts\Actuation\Servos\ClosedLoopMotor as ClosedLoopMotorInterface;
use Fabricate\Contracts\Actuation\Servos\ServoComponent as ServoComponentContract;
use Fabricate\IntegratedCircuits\ActuatorIC;

abstract class Servo extends ActuationComponent implements ServoComponentContract
{
    public function __construct(
        protected ClosedLoopMotorInterface $actuator,
    ) {}

    public function actuator(): ClosedLoopMotorInterface
    {
        return $this->actuator;
    }

    public function to(int $degrees, int $ms = 0, int $rate = 0): void
    {
        $this->actuator->to($degrees, $ms, $rate);
    }

    public function pulse(?int $ns = null): int
    {
        return $this->actuator->pulse($ns);
    }

    public function calibrate(int $min, int $max, ?int $stop = null): static
    {
        $this->actuator->calibrate($min, $max, $stop);

        return $this;
    }

    public function enable(): void
    {
        $this->actuator->enable();
    }

    public function disable(): void
    {
        $this->actuator->disable();
    }

    public function enabled(): bool
    {
        return $this->actuator->enabled();
    }

    public function getPosition(): int
    {
        return $this->actuator->getPosition();
    }

    public static function buildWith(ActuatorIC $actuator): static
    {
        if ($actuator instanceof ClosedLoopMotorInterface) {
            return new static($actuator);
        }

        throw new ActuationException($actuator::class.' must implement ClosedLoopMotor');
    }
}
