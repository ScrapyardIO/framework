<?php

namespace GPIO\PWM;

use GPIO\Common\GPIOTransport;
use GPIO\Contracts\PWM\PWMDriverAdapter as PWMDriverAdapterInterface;
use GPIO\Contracts\PWM\PWMConnectionHandle as PWMConnectionHandleInterface;
use GPIO\Contracts\PWM\PWMChannelTransport as PWMChannelTransportContract;
use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;

class PWMChannel extends GPIOTransport implements PWMChannelTransportContract
{
    public function __construct(
        PWMConnectionHandleInterface $handle,
        PWMDriverAdapterInterface $driver,
    ) {
        parent::__construct($driver, $handle);
    }

    public function setDutyCycle(int $value): int
    {
        return $this->driver()->setDutyCycle($value, $this->handle());
    }

    public function getDutyCycle(): int
    {
        return $this->driver()->getDutyCycle($this->handle());
    }

    public function setPeriod(int $value): int
    {
        return $this->driver()->setPeriod($value, $this->handle());
    }

    public function getPeriod(): int
    {
        return $this->driver()->getPeriod($this->handle());
    }

    public function setEnable(bool $value): bool
    {
        return $this->driver()->setEnable($value, $this->handle());
    }

    public function getEnable(): bool
    {
        return $this->driver()->getEnable($this->handle());
    }

    public function setPolarity(string $value): string
    {
        return $this->driver()->setPolarity($value, $this->handle());
    }

    public function getPolarity(): string
    {
        return $this->driver()->getPolarity($this->handle());
    }

    protected function driver(): PWMDriverAdapterInterface
    {
        /** @var PWMDriverAdapterInterface */
        return $this->driver;
    }

    protected function handle(): PWMConnectionHandleInterface
    {
        /** @var PWMConnectionHandleInterface */
        return $this->handle;
    }
}
