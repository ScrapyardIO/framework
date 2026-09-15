<?php

namespace GeneralPurposeIO\SPI\Factory;

use GeneralPurposeIO\Contracts\SPI\SPIDriver;
use GeneralPurposeIO\Contracts\SPI\SPIEndianness;
use GeneralPurposeIO\Contracts\SPI\SPIMode;

abstract class SPIFactory
{
    public SPIMode $spi_mode = SPIMode::MODE_0;

    public int $speed = 800_000;

    public SPIEndianness $endianness = SPIEndianness::MSB;

    abstract protected function assertReady(): void;
    abstract public function driver(): SPIDriver;

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
}