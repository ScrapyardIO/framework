<?php

namespace GeneralPurposeIO\Digital\Bus;

use GeneralPurposeIO\Digital\DigitalInputPin;
use GeneralPurposeIO\Digital\DigitalOutputPin;
use GeneralPurposeIO\Digital\Drivers\UsbDigitalIODriver;

class UsbDigitalIOBus extends DigitalIOBus
{
    public function __construct(
        protected UsbDigitalIODriver $driver
    ) {}

    public function input(int $pin): DigitalInputPin
    {
        mpsse_configure_pin_direction($this->driver->getContext(), $pin, false);
        return new DigitalInputPin($pin, $this->driver);
    }

    public function output(int $pin): DigitalOutputPin
    {
        mpsse_configure_pin_direction($this->driver->getContext(), $pin, true);
        return new DigitalOutputPin($pin, $this->driver);
    }
}