<?php

namespace GPIO\SPI;

use GPIO\Common\GPIOConnectionFactory;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\Common\GPIOConnectionBus;
use GPIO\Contracts\Digital\DigitalPinsOnBus;
use GPIO\Contracts\SPI\SPIDriverAdapter as SPIDriverAdapterInterface;
use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterInterface;
use GPIO\Contracts\SPI\SPIConnectionFactory as SPIConnectionFactoryContract;
use GPIO\Contracts\SPI\SPIEndianness;
use GPIO\Contracts\SPI\SPIException;
use GPIO\Contracts\SPI\SPIMode;

#[CarrierFactory('spi')]
class SPIConnectionFactory extends GPIOConnectionFactory implements SPIConnectionFactoryContract
{
    use DigitalPinsOnBus;

    /** @var SPIDriverAdapterInterface  */
    protected GPIODriverAdapterInterface $driver_adapter;

    public string|int|null $master_host_device = null;

    public SPIMode $spi_mode = SPIMode::MODE_0;

    public int $chip_select = 0;

    public int $speed = 800_000;

    public int $bits_per_word = 8;

    public SPIEndianness $endianness = SPIEndianness::MSB;

    public function __construct(
        string $driver
    ) {
        parent::__construct($driver);
    }

    public function mode(SPIMode $value): static
    {
        $this->spi_mode = $value;
        return $this;
    }

    public function speed(int $value): static
    {
        $this->speed = $value;
        return $this;
    }

    public function endianness(SPIEndianness $endianness): static
    {
        $this->endianness = $endianness;
        return $this;
    }

    public function chipSelect(int $value): static
    {
        $this->chip_select = $value;
        return $this;
    }

    public function bitsPerByte(int $value): static
    {
        $this->bits_per_word = $value;
        return $this;
    }

    public function device(int|string $value): static
    {
        $this->master_host_device = $value;
        return $this;
    }


    /**
     * @throws SPIException
     */
    public function create(): SPI|SPIConnectionBus
    {
        if(is_null($this->master_host_device)) {
            throw SPIException::missingMasterDevice();
        }

        return $this->driver_adapter->buildConnection(
            $this->master_host_device,
            $this->chip_select,
            $this->spi_mode,
            $this->speed,
            $this->bits_per_word,
            $this->endianness,
            $this->gpio_chip,
            $this->digital_pins
        );
    }
}
