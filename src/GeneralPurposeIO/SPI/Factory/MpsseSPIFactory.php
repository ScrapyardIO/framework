<?php

namespace GeneralPurposeIO\SPI\Factory;

use GeneralPurposeIO\Contracts\SPI\SPIMode;
use GeneralPurposeIO\SPI\Bus\UsbSPIBus;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use GeneralPurposeIO\SPI\Drivers\UsbSPIDriver;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;
use GeneralPurposeIO\Contracts\SPI\SPIEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;

class MpsseSPIFactory extends SPIFactory
{
    public function __construct(
        protected MpsseSupportedDevice $device
    ) {}

    /**
     * @throws SPIException
     */
    public function bus(): UsbSPIBus
    {
        $driver = $this->driver();

        return new UsbSPIBus($driver);
    }

    /**
     * @throws SPIException
     */
    public function driver(): UsbSPIDriver
    {
        $this->assertReady();
        $error = '';
        $interface = $this->device->interface();

        $context = mpsse_open(
            vid: FtdiVendorId::FTDI->value,
            pid: $this->device->productId(),
            mode: match ($this->spi_mode) {
                SPIMode::MODE_1 => MPSSEMode::SPI1,
                SPIMode::MODE_2 => MPSSEMode::SPI2,
                SPIMode::MODE_3 => MPSSEMode::SPI3,
                default => MPSSEMode::SPI0,
            },
            freq: $this->speed,
            endianness: $this->endianness === SPIEndianness::MSB
                ? MPSSEEndianness::MSB
                : MPSSEEndianness::LSB,
            iface: $interface,
            error: $error,
        );

        if (! empty($error) || is_null($context)) {
            throw new SPIException("MPSSE SPI context for [{$this->device->value}] could not be opened. {$error}");
        }

        return new UsbSPIDriver($context);
    }

    /**
     * @throws SPIException
     */
    protected function assertReady(): void
    {
        if (is_null($this->device)) {
            throw SPIException::missingMasterDevice();
        }
    }
}