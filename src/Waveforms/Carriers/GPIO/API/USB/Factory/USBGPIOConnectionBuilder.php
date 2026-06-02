<?php

namespace Waveforms\Carriers\GPIO\API\USB\Factory;

use Exception;
use Microscrap\Bindings\FTDI\Enums\FtdiProductId;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;
use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEInterface;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Waveforms\Carriers\GPIO\API\USB\Exceptions\UsbGPIOException;
use Waveforms\Carriers\GPIO\API\USB\UsbGPIOBus;
use Waveforms\Carriers\GPIO\API\USB\UsbGPIOInput;
use Waveforms\Carriers\GPIO\API\USB\UsbGPIOOutput;
use Waveforms\Carriers\GPIO\Contracts\GPIOInput;
use Waveforms\Carriers\GPIO\Contracts\GPIOOutput;
use Waveforms\Carriers\GPIO\Exceptions\GPIOException;
use Waveforms\Carriers\GPIO\Factory\GPIOConnectionBuilder;

class USBGPIOConnectionBuilder extends GPIOConnectionBuilder
{
    private ?FtdiProductId $device = null;

    public MPSSEClockRate $clock_rate = MPSSEClockRate::ONE_MHZ;

    public MPSSEEndianness $endianness = MPSSEEndianness::MSB;

    public MPSSEInterface $iface = MPSSEInterface::IFACE_A;

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

        throw UsbGPIOException::unsupportedDevice($device);
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

    public function addInput(GPIOInput $input): static
    {
        if ($input instanceof UsbGPIOInput) {
            $this->desired_gpio[$input->alias] = $input;

            return $this;
        }

        throw GPIOException::wrongGPIOPinType($input::class, 'usb');
    }

    public function addOutput(GPIOOutput $output): static
    {
        if ($output instanceof UsbGPIOOutput) {
            $this->desired_gpio[$output->alias] = $output;

            return $this;
        }

        throw GPIOException::wrongGPIOPinType($output::class, 'usb');
    }

    public function consumer(string $name): static
    {
        $this->request_consumer = $name;

        return $this;
    }

    public function connection(): string
    {
        return 'usb';
    }

    public function boot(): UsbGPIOBus
    {
        $error = '';
        $usb_context = mpsse_open(
            vid: FtdiVendorId::FTDI->value,
            pid: $this->device->value,
            mode: MPSSEMode::GPIO,
            freq: $this->clock_rate->value,
            endianness: $this->endianness,
            iface: $this->iface,
            error: $error,
        );

        if (! is_null($usb_context)) {
            foreach ($this->desired_gpio as $line) {
                mpsse_configure_pin_direction($usb_context, $line->pin, $line instanceof UsbGPIOOutput);
            }

            return new UsbGPIOBus($usb_context, $this->desired_gpio);
        }

        throw UsbGPIOException::couldNotOpenDeviceContext($this->device->name, $error);
    }
}
