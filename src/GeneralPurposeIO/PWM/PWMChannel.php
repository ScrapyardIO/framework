<?php

namespace GeneralPurposeIO\PWM;

use GeneralPurposeIO\Contracts\PWM\PWMDriver;
use GeneralPurposeIO\Contracts\PWM\PWMPolarity;

class PWMChannel
{
    public function __construct(
        public readonly int $channel,
        protected PWMDriver $driver,
    ) {}

    public function setDutyCycle(int $value): int
    {
        return $this->driver->setDutyCycle($this->channel, $value);
    }

    public function getDutyCycle(): int
    {
        return $this->driver->getDutyCycle($this->channel);
    }

    public function setPeriod(int $value): int
    {
        return $this->driver->setPeriod($this->channel,$value);
    }

    public function getPeriod(): int
    {
        return $this->driver->getPeriod($this->channel);
    }

    public function setEnable(bool $value): bool
    {
        return $this->driver->setEnable($this->channel, $value);
    }

    public function getEnable(): bool
    {
        return $this->driver->getEnable($this->channel);
    }

    public function setPolarity(PWMPolarity $value): PWMPolarity
    {
        return $this->driver->setPolarity($this->channel, $value);
    }

    public function getPolarity(): PWMPolarity
    {
        return $this->driver->getPolarity($this->channel);
    }

    public function close(): void
    {
        $this->driver->close($this->channel);
    }
}