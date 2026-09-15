<?php

namespace GeneralPurposeIO\SPI\Bus;

use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\SPI\Drivers\PosixSPIDriver;
use GeneralPurposeIO\SPI\SPIDevice;

class PosixSPIBus extends SPIBus
{
    public function __construct(
        protected PosixSPIDriver $driver
    ) {}

    /**
     * @throws SPIException
     */
    public function select(int $chip): SPIDevice
    {
        $path = "{$this->driver->partial_path}{$chip}";
        $master = substr($this->driver->partial_path, -2, 1);
        if(file_exists($path))
        {
            $posix_spi_device = spi_open($path, $this->driver->mode_flags, $this->driver->speed, $this->driver->bits_per_word);

            if (is_null($posix_spi_device)) {
                throw SPIException::couldNotOpenSPIDevice($master, $chip);
            }

            return new SPIDevice($posix_spi_device, $this->driver);
        }


        throw SPIException::couldNotOpenSPIDevice($master, $chip);
    }

    public function canServeDigitalPins(): bool
    {
        return false;
    }
}