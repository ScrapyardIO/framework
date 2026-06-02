<?php

namespace Waveforms\Carriers\SPI\API\Native\Factory;

use Exception;
use Microscrap\Bindings\SPI\Enums\SPIMode as NativeSPIMode;
use Waveforms\Carriers\SPI\API\Native\Exceptions\NativeSPIException;
use Waveforms\Carriers\SPI\API\Native\NativeSPIDevice;
use Waveforms\Carriers\SPI\Enums\SPIEndianness;
use Waveforms\Carriers\SPI\Exceptions\SPIException;
use Waveforms\Carriers\SPI\Factory\SPIConnectionBuilder;

class NativeSPIConnectionBuilder extends SPIConnectionBuilder
{
    private string $device_path = '/dev/spidev';

    public ?int $bus = null;

    public int $chip = 0;

    public int $bits_per_word = 8;

    /**
     * @throws Exception
     */
    public function firstly(int|string $master): static
    {
        if (is_string($master)) {
            throw new Exception(static::class.' requires the bus to be an int.');
        }

        return $this->bus($master);
    }

    public function bus(int $bus): static
    {
        $this->bus = $bus;

        return $this;
    }

    public function chip(int $chip): static
    {
        $this->chip = $chip;

        return $this;
    }

    public function bitsPerWord(int $bits): static
    {
        $this->bits_per_word = $bits;

        return $this;
    }

    public function endianness(SPIEndianness $endianness): static
    {
        $this->endianness = $endianness;

        return $this;
    }

    public function boot(): NativeSPIDevice
    {
        if (is_null($this->bus)) {
            throw NativeSPIException::missingBus();
        }

        $path = "{$this->device_path}{$this->bus}.{$this->chip}";

        // Fold the LSB-first bit (SPI_LSB_FIRST = 0x08) into the spidev mode word.
        // Note: the BCM2835 controller on Raspberry Pi has no hardware LSB-first
        // support, so this ioctl is rejected there — devices like the PN532 need
        // software bit reversal on that platform instead.
        $mode = $this->spi_mode->value;
        if ($this->endianness === SPIEndianness::LSB) {
            $mode |= NativeSPIMode::LSB_FIRST->value;
        }

        $spi_device = spi_open($path, $mode, $this->speed, $this->bits_per_word);

        if (! is_null($spi_device)) {
            return new NativeSPIDevice($spi_device);
        }

        throw SPIException::couldNotOpenSPIDevice($path);
    }

    public function connection(): string
    {
        return 'native';
    }
}
