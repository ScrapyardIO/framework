<?php

namespace BareMetal\Framebuffers;

use GPIO\Contracts\Common\GPIOException;
use GPIO\Contracts\I2C\I2CDriverAdapter as I2CDriverAdapterInterface;
use GPIO\Contracts\SPI\SPIDriverAdapter as SPIDriverAdapterInterface;
use GPIO\Contracts\PWM\PWMDriverAdapter as PWMDriverAdapterInterface;
use GPIO\Contracts\UART\UARTDriverAdapter as UARTDriverAdapterInterface;
use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterInterface;
use GPIO\Contracts\Common\CarrierDriverManager as CarrierDriverManagerContract;
use GPIO\Contracts\Digital\DigitalPinDriverAdapter as DigitalPinDriverAdapterInterface;

abstract class CarrierDriverManager implements CarrierDriverManagerContract
{
    public function __construct(
        protected string $library
    ) {}

    /**
     * @throws GPIOException
     */
    protected function createDigitalInputDriver(): DigitalPinDriverAdapterInterface
    {
        throw GPIOException::unsupportedDriverProtocol('digital-int', $this->library);
    }

    /**
     * @throws GPIOException
     */
    protected function createDigitalOutputDriver(): DigitalPinDriverAdapterInterface
    {
        throw GPIOException::unsupportedDriverProtocol('digital-out', $this->library);
    }

    /**
     * @throws GPIOException
     */
    protected function createI2CDriver(): I2CDriverAdapterInterface
    {
        throw GPIOException::unsupportedDriverProtocol('i2c', $this->library);
    }

    /**
     * @throws GPIOException
     */
    protected function createSPIDriver(): SPIDriverAdapterInterface
    {
        throw GPIOException::unsupportedDriverProtocol('spi', $this->library);
    }

    /**
     * @throws GPIOException
     */
    protected function createPWMDriver(): PWMDriverAdapterInterface
    {
        throw GPIOException::unsupportedDriverProtocol('pwm', $this->library);
    }

    /**
     * @throws GPIOException
     */
    protected function createUARTDriver(): UARTDriverAdapterInterface
    {
        throw GPIOException::unsupportedDriverProtocol('uart', $this->library);
    }

    /**
     * @throws GPIOException
     */
    public function driver(string $name): GPIODriverAdapterInterface
    {
        return match($name) {
            'digital-in' => $this->createDigitalInputDriver(),
            'digital-out' => $this->createDigitalOutputDriver(),
            'i2c' => $this->createI2CDriver(),
            'spi' => $this->createSPIDriver(),
            'uart' => $this->createUARTDriver(),
            'pwm' => $this->createPWMDriver(),
            default => throw GPIOException::invalidProperty($name, static::class)
        };
    }
}
