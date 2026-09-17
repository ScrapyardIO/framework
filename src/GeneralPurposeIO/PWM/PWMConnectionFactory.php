<?php

namespace GeneralPurposeIO\PWM;

abstract class PWMConnectionFactory
{
    public function __construct(
        public string|int $device,
        protected PWMConnectionDriver $driver
    ) {}

    abstract protected function device(): mixed;
    abstract protected function getHandle(): mixed;

    public function register(): PWMConnectionDriver
    {
        return $this->driver->register($this->device, $this->getHandle());
    }
}
