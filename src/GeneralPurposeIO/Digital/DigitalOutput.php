<?php

namespace GeneralPurposeIO\Digital;

use GeneralPurposeIO\Digital\Drivers\DigitalPinDriver;

class DigitalOutput extends DigitalPin
{
    public function __construct(
        int $pin,
        string $consumer,
        DigitalPinDriver $driver,
        bool $default_state
    )
    {
        parent::__construct($pin, $consumer, $driver);
        $default_state ? $this->high() : $this->low();
    }

    public function low(): bool
    {
        return $this->driver->write($this->pin, false);
    }

    public function high(): bool
    {
        return $this->driver->write($this->pin, true);
    }


}
