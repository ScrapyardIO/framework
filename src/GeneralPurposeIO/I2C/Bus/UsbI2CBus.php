<?php

namespace GeneralPurposeIO\I2C\Bus;

use GeneralPurposeIO\Digital\DigitalInputPin;
use GeneralPurposeIO\Digital\DigitalOutputPin;
use GeneralPurposeIO\Digital\Drivers\UsbDigitalIODriver;
use GeneralPurposeIO\I2C\Drivers\UsbI2CDriver;
use GeneralPurposeIO\I2C\I2CSlave;

class UsbI2CBus extends I2CBus
{
    public function __construct(
        protected UsbI2CDriver $driver
    ) {}

    public function slave(int $address): I2CSlave|false
    {
        $results = false;

        if (($address > 0x07) && ($address <= 0x77)) {
            $results = new I2CSlave($address, $this->driver);
        }

        return $results;
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