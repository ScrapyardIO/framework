<?php

namespace BareMetal\Actuation\Servos;

use BareMetal\Actuation\ActuationComponent;
use BareMetal\Contracts\Actuators\Servos\ClosedLoopMotor as ClosedLoopMotorInterface;
use BareMetal\Contracts\Actuators\Servos\ServoComponent as ServoComponentContract;

/**
 * Reality-facing servo wrapper beside the fan components — DOSR ICs such as
 * GenericPositionalServo / GenericContinuousServo implement the motor contracts;
 * these components forward the shared ClosedLoopMotor surface to the app.
 */
abstract class ServoComponent extends ActuationComponent implements ServoComponentContract
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
}
