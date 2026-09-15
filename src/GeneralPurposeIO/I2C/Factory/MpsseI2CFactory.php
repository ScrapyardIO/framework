<?php

namespace GeneralPurposeIO\I2C\Factory;

use GeneralPurposeIO\I2C\Bus\UsbI2CBus;
use GeneralPurposeIO\I2C\Drivers\UsbI2CDriver;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;
use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;

class MpsseI2CFactory extends I2CFactory
{
    public MPSSEEndianness $endianness = MPSSEEndianness::MSB;

    public MPSSEClockRate $clock_rate = MPSSEClockRate::FOUR_HUNDRED_KHZ;

    public function __construct(
        protected MpsseSupportedDevice $device
    ) {}

    public function endianness(MPSSEEndianness $endianness): static
    {
        $this->endianness = $endianness;

        return $this;
    }

    public function clockRate(MPSSEClockRate $rate): static
    {
        $this->clock_rate = $rate;

        return $this;
    }

    /**
     * @throws I2CException
     */
    public function bus(): UsbI2CBus
    {
        $driver = $this->driver();

        return new UsbI2CBus($driver);
    }

    /**
     * @throws I2CException
     */
    public function driver(): UsbI2CDriver
    {
        $this->assertReady();
        $error = '';
        $interface = $this->device->interface();

        $context = mpsse_open(
            vid: FtdiVendorId::FTDI->value,
            pid: $this->device->productId(),
            mode: MPSSEMode::I2C,
            freq: $this->clock_rate->value,
            endianness: $this->endianness,
            iface: $interface,
            error: $error,
        );

        if (! empty($error) || is_null($context)) {
            throw new I2CException("MPSSE I2C context for [{$this->device->value}] could not be opened. {$error}");
        }

        return new UsbI2CDriver($context);

    }

    /**
     * @throws I2CException
     */
    protected function assertReady(): void
    {
        if (is_null($this->device)) {
            throw I2CException::missingMasterDevice();
        }
    }
}