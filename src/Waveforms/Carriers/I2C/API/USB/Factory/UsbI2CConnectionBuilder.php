<?php

namespace Waveforms\Carriers\I2C\API\USB\Factory;

use Exception;
use Microscrap\Bindings\FTDI\Enums\FtdiProductId;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;
use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEInterface;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Waveforms\Carriers\I2C\API\USB\Exceptions\UsbI2CException;
use Waveforms\Carriers\I2C\API\USB\UsbI2CDevice;
use Waveforms\Carriers\I2C\Factory\I2CConnectionBuilder;

class UsbI2CConnectionBuilder extends I2CConnectionBuilder
{
    private ?FtdiProductId $device = null;

    public MPSSEClockRate $clock_rate = MPSSEClockRate::ONE_MHZ;

    public MPSSEEndianness $endianness = MPSSEEndianness::MSB;

    public MPSSEInterface $iface = MPSSEInterface::IFACE_A;

    /**
     * @throws Exception
     */
    public function firstly(int|string $chip_device): static
    {
        if (is_int($chip_device)) {
            throw new Exception(static::class.' requires the device to be a string.');
        }

        return $this->device($chip_device);
    }

    public function device(string $device): static
    {
        $device = strtoupper($device);
        if (mpsse_check_ftdi_device($device)) {
            $this->device = FtdiProductId::{$device};

            return $this;
        }

        throw UsbI2CException::unsupportedDevice($device);
    }

    public function interface(MPSSEInterface $iface): static
    {
        $this->iface = $iface;

        return $this;
    }

    public function endianness(MPSSEEndianness $endianness): static
    {
        $this->endianness = $endianness;

        return $this;
    }

    public function clockRate(MPSSEClockRate $clock_rate): static
    {
        $this->clock_rate = $clock_rate;

        return $this;
    }

    public function boot(): UsbI2CDevice
    {
        if (is_null($this->device)) {
            throw UsbI2CException::missingDevice();
        }

        if (is_null($this->slave_address)) {
            throw UsbI2CException::missingSlaveAddress();
        }

        $error = '';
        $usb_context = mpsse_open(
            vid: FtdiVendorId::FTDI->value,
            pid: $this->device->value,
            mode: MPSSEMode::I2C,
            freq: $this->clock_rate->value,
            endianness: $this->endianness,
            iface: $this->iface,
            error: $error,
        );

        if (! is_null($usb_context)) {
            return new UsbI2CDevice($usb_context, $this->slave_address);
        }

        throw UsbI2CException::couldNotOpenDeviceContext($this->device->name, $error);
    }

    public function connection(): string
    {
        return 'usb';
    }
}
