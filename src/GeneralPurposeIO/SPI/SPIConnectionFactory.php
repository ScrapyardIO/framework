<?php

namespace GeneralPurposeIO\SPI;

use GeneralPurposeIO\Contracts\SPI\SPIMode;
use GeneralPurposeIO\Contracts\SPI\SPIEndianness;

abstract class SPIConnectionFactory
{
    public SPIMode $spi_mode = SPIMode::MODE_0;

    public int $speed = 800_000;

    public SPIEndianness $endianness = SPIEndianness::MSB;

    public int $chip_select = 0;

    public function __construct(
        public string|int $device,
        protected SPIConnectionDriver $driver
    ) {}

    abstract protected function device(): mixed;
    abstract public function getHandle(): mixed;
    abstract public function chipSelect(int $chip_select): static;

    public function mode(SPIMode|int $value): static
    {
        if(is_int($value)) {
            $value = SPIMode::from($value);
        }
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

    public function register(): SPIConnectionDriver
    {
        return $this->driver->register($this->device, $this->getHandle());
    }

}