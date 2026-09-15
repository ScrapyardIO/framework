<?php

namespace GeneralPurposeIO\SPI\Factory;

use GeneralPurposeIO\Contracts\SPI\SPIDriver;
use GeneralPurposeIO\Contracts\SPI\SPIEndianness;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\SPI\Bus\PosixSPIBus;
use GeneralPurposeIO\SPI\Drivers\PosixSPIDriver;
use Microscrap\Bindings\SPI\Enums\SPIMode as NativeSPIMode;

class PosixSPIFactory extends SPIFactory
{
    public function __construct(
        protected int $device
    ) {}

    public int $bits_per_word = 8;

    public function bitsPerByte(int $value): static
    {
        $this->bits_per_word = $value;

        return $this;
    }

    /**
     * @throws SPIException
     */
    public function bus(): PosixSPIBus
    {
        $driver = $this->driver();

        return new PosixSPIBus($driver);
    }

    /**
     * @throws SPIException
     */
    public function driver(): PosixSPIDriver
    {
        $this->assertReady();

        $mode = $this->spi_mode->value;
        if ($this->endianness === SPIEndianness::LSB) {
            $mode |= NativeSPIMode::LSB_FIRST->value;
        }

        $partial_path = "/dev/spidev{$this->device}.";

        return new PosixSPIDriver($partial_path, $mode, $this->speed, $this->bits_per_word);
    }

    /**
     * @throws SPIException
     */
    protected function assertReady(): void
    {
        if (empty(glob("/dev/spidev{$this->device}.*"))) {
            throw new SPIException("Device {$this->device} does not exist");
        }
    }


}