<?php

namespace GeneralPurposeIO\SPI\Bus;

use GeneralPurposeIO\Digital\DigitalInputPin;
use GeneralPurposeIO\Digital\DigitalOutputPin;
use GeneralPurposeIO\Digital\Drivers\UsbDigitalIODriver;
use GeneralPurposeIO\SPI\Drivers\UsbSPIDriver;
use GeneralPurposeIO\SPI\SPIDevice;

class UsbSPIBus extends SPIBus
{
    public function __construct(
        protected UsbSPIDriver $driver
    ) {}

    public function select(int $chip): SPIDevice
    {
        return new SPIDevice($chip, $this->driver);
    }

    public function input(int $pin): DigitalInputPin
    {
        mpsse_configure_pin_direction($this->driver->getContext(), $pin, false);
        $driver = new UsbDigitalIODriver($this->driver->getContext());
        return new DigitalInputPin($pin, $driver);
    }

    public function output(int $pin): DigitalOutputPin
    {
        mpsse_configure_pin_direction($this->driver->getContext(), $pin, true);
        $driver = new UsbDigitalIODriver($this->driver->getContext());
        return new DigitalOutputPin($pin, $driver);
    }

    public function canServeDigitalPins(): bool
    {
        return true;
    }
}