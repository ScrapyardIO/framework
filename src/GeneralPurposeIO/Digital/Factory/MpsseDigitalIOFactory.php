<?php

namespace GeneralPurposeIO\Digital\Factory;

use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Digital\Bus\UsbDigitalIOBus;
use GeneralPurposeIO\Digital\Drivers\UsbDigitalIODriver;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;
use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;

class MpsseDigitalIOFactory extends DigitalIOFactory
{
    public MPSSEEndianness $endianness = MPSSEEndianness::MSB;

    public MPSSEClockRate $clock_rate = MPSSEClockRate::ONE_MHZ;

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
     * @throws DigitalIOException
     */
    public function bus(): UsbDigitalIOBus
    {
        $driver = $this->driver();

        return new UsbDigitalIOBus($driver);
    }
    /**
     * @throws DigitalIOException
     */
    public function driver(): UsbDigitalIODriver
    {
        $this->assertReady();
        $error = '';
        $interface = $this->device->interface();

        $context = mpsse_open(
            vid: FtdiVendorId::FTDI->value,
            pid: $this->device->productId(),
            mode: MPSSEMode::GPIO,
            freq: $this->clock_rate->value,
            endianness: $this->endianness,
            iface: $interface,
            error: $error,
        );

        if (! empty($error) || is_null($context)) {
            throw new DigitalIOException("MPSSE DigitalIO context for [{$this->device->value}] could not be opened. {$error}");
        }

        return new UsbDigitalIODriver($context);
    }

    /**
     * @throws DigitalIOException
     */
    protected function assertReady(): void
    {
        if (is_null($this->device)) {
            throw DigitalIOException::missingDigitalPinDevice();
        }
    }
}