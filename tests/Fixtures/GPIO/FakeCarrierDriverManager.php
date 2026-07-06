<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO;

use GPIO\Common\CarrierDriverManager;
use GPIO\Contracts\Digital\DigitalPinDriverAdapter as DigitalPinDriverAdapterInterface;
use GPIO\Contracts\I2C\I2CDriverAdapter as I2CDriverAdapterInterface;
use GPIO\Contracts\SPI\SPIDriverAdapter as SPIDriverAdapterInterface;
use GPIO\Contracts\UART\UARTDriverAdapter as UARTDriverAdapterInterface;

/**
 * A fake carrier supporting every protocol except PWM, mirroring the real
 * usb carrier's shape (see Microscrap\ScrapyardIODrivers\USB\ScrapyardIOUSBManager)
 * without depending on that sibling package or its hardware extensions.
 */
class FakeCarrierDriverManager extends CarrierDriverManager
{
    public function __construct()
    {
        parent::__construct('fake');
    }

    protected function createDigitalInputDriver(): DigitalPinDriverAdapterInterface
    {
        return new FakeGPIODriverAdapter;
    }

    protected function createDigitalOutputDriver(): DigitalPinDriverAdapterInterface
    {
        return new FakeGPIODriverAdapter;
    }

    protected function createI2CDriver(): I2CDriverAdapterInterface
    {
        return new FakeI2CDriverAdapter;
    }

    protected function createSPIDriver(): SPIDriverAdapterInterface
    {
        return new FakeSPIDriverAdapter;
    }

    protected function createUARTDriver(): UARTDriverAdapterInterface
    {
        return new FakeUARTDriverAdapter;
    }
}
