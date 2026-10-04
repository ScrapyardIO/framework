<?php

namespace GeneralPurposeIO\I2C;

abstract class I2CConnectionFactory
{
    public function __construct(
        public string|int $device,
        protected I2CConnectionDriver $driver
    ) {}

    abstract protected function device(): mixed;
    abstract protected function getHandle(): mixed;

    public function register(): I2CConnectionDriver
    {
        return $this->driver->register($this->device, $this->getHandle());
    }
}