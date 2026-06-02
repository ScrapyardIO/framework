<?php

namespace Waveforms\Carriers\SPI\Factory;

use Waveforms\Carriers\SPI\Enums\SPIEndianness;
use Waveforms\Carriers\SPI\Enums\SPIMode;
use Waveforms\Carriers\SPI\SPIDevice;
use Waveforms\Factory\CarrierFactory;

abstract class SPIConnectionBuilder extends CarrierFactory
{
    public SPIMode $spi_mode = SPIMode::MODE_0;

    public int $speed = 500_000;

    public SPIEndianness $endianness = SPIEndianness::MSB;

    abstract public function firstly(int|string $master): static;

    abstract public function boot(): SPIDevice;

    abstract public function endianness(SPIEndianness $endianness): static;

    public function mode(SPIMode $mode): static
    {
        $this->spi_mode = $mode;

        return $this;
    }

    public function speed(int $hz): static
    {
        $this->speed = $hz;

        return $this;
    }
}
