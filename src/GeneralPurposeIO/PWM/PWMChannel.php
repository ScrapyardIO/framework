<?php

namespace GeneralPurposeIO\PWM;

use GeneralPurposeIO\Contracts\PWM\PWMPolarity;
use GeneralPurposeIO\PWM\Drivers\PWMDriver;

class PWMChannel
{
    public function __construct(
        protected PWMDriver $driver,
        protected string $consumer,
    ) {}

    public function name(): string
    {
        return $this->consumer;
    }

    public function setDutyCycle(int $value): int
    {
        return $this->driver->setDutyCycle($value);
    }

    public function getDutyCycle(): int
    {
        return $this->driver->getDutyCycle();
    }

    public function setPeriod(int $value): int
    {
        return $this->driver->setPeriod($value);
    }

    public function getPeriod(): int
    {
        return $this->driver->getPeriod();
    }

    public function setEnable(bool $value): bool
    {
        return $this->driver->setEnable($value);
    }

    public function getEnable(): bool
    {
        return $this->driver->getEnable();
    }

    public function setPolarity(PWMPolarity $value): PWMPolarity
    {
        return $this->driver->setPolarity($value);
    }

    public function getPolarity(): PWMPolarity
    {
        return $this->driver->getPolarity();
    }

    public function close(): void
    {
        $this->driver->close();
    }
}
