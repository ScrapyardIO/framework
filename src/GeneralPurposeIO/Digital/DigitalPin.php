<?php

namespace GeneralPurposeIO\Digital;

use GeneralPurposeIO\Digital\Drivers\DigitalPinDriver;

abstract class DigitalPin
{
    public function __construct(
        protected int $pin,
        protected string $consumer,
        protected DigitalPinDriver $driver,

    ) {}

    public function isLow(): bool
    {
        return !$this->driver->read($this->pin);
    }

    public function isHigh(): bool
    {
        return $this->driver->read($this->pin);
    }

    public function read(): bool
    {
        return $this->driver->read($this->pin);
    }

    public function close(): null
    {
        $this->driver->close();
        return null;
    }
}
