<?php

namespace GeneralPurposeIO\Digital;

abstract class DigitalIOConnectionFactory
{
    public function __construct(
        public string|int $device,
        protected DigitalIOConnectionDriver $driver
    ) {}

    abstract protected function device(): mixed;
    abstract protected function getHandle(): mixed;

    public function register(): DigitalIOConnectionDriver
    {
        return $this->driver->register($this->device, $this->getHandle());
    }
}