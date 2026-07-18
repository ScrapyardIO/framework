<?php

namespace GeneralPurposeIO\Common;

use Fabricate\Contracts\Core\Machine;
use GeneralPurposeIO\I2C\I2CCarrierManager;
use GeneralPurposeIO\PWM\PWMCarrierManager;
use GeneralPurposeIO\SPI\SPICarrierManager;
use GeneralPurposeIO\UART\UARTCarrierManager;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Digital\DigitalInputCarrierManager;
use GeneralPurposeIO\Digital\DigitalOutputCarrierManager;
use GeneralPurposeIO\Contracts\Common\GPIOProtocolFactory as FactoryContract;
use GeneralPurposeIO\Contracts\Common\CarrierDriverManager as CarrierManager;

class GPIOProtocolManager implements FactoryContract
{
    public function __construct(protected Machine $app) {}

    protected function createDigitalOutputDriver(): DigitalOutputCarrierManager
    {
        return new DigitalOutputCarrierManager($this->app);
    }

    protected function createDigitalInputDriver(): DigitalInputCarrierManager
    {
        return new DigitalInputCarrierManager($this->app);
    }

    protected function createI2CDriver(): I2CCarrierManager
    {
        return new I2CCarrierManager($this->app);
    }

    protected function createSPIDriver(): SPICarrierManager
    {
        return new SPICarrierManager($this->app);
    }

    protected function createUARTDriver(): UARTCarrierManager
    {
        return new UARTCarrierManager($this->app);
    }

    protected function createPWMDriver(): PWMCarrierManager
    {
        return new PWMCarrierManager($this->app);
    }

    /**
     * @throws GPIOException
     */
    public function __call(string $name, $arguments): CarrierManager
    {
        return $this->protocol($name);
    }

    /**
     * @throws GPIOException
     */
    public function protocol(string $name): CarrierManager
    {
        return match($name) {
            'i2c' => $this->createI2CDriver(),
            'spi' => $this->createSPIDriver(),
            'pwm' => $this->createPWMDriver(),
            'uart' => $this->createUARTDriver(),
            'digital-in' => $this->createDigitalInputDriver(),
            'digital-out' => $this->createDigitalOutputDriver(),
            default => throw GPIOException::invalidProperty($name, static::class)
        };
    }
}
