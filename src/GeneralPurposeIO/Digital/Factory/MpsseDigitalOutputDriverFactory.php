<?php

namespace GeneralPurposeIO\Digital\Factory;

use GeneralPurposeIO\Contracts\Digital\DigitalPinException;
use GeneralPurposeIO\Digital\DigitalOutput;
use GeneralPurposeIO\Digital\Drivers\UsbDigitalPinDriver;
use GeneralPurposeIO\Digital\MultipleDigitalPins;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;
use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;

class MpsseDigitalOutputDriverFactory extends MpsseDigitalPinDriverFactory
{
    public bool $default_state = false;

    protected MPSSEEndianness $endianness = MPSSEEndianness::MSB;

    protected MPSSEClockRate $clock_rate = MPSSEClockRate::ONE_MHZ;

    /**
     * @throws DigitalPinException
     */
    public function create(): DigitalOutput|MultipleDigitalPins
    {
        $this->assertReady();

        $error = '';
        $device = MpsseSupportedDevice::from($this->device);
        $interface = $device->interface();
        $context = mpsse_open(
            vid: FtdiVendorId::FTDI->value,
            pid: $device->productId(),
            mode: MPSSEMode::GPIO,
            freq: $this->clock_rate->value,
            endianness: $this->endianness,
            iface: $interface,
            error: $error,
        );

        if (! empty($error)) {
            throw new DigitalPinException("MPSSE Context Error - {$error}");
        }

        mpsse_configure_pin_direction($context, $this->pin, true);

        if (count($this->addl_pins) > 0) {
            $this->configureMpsseAddlDirections($context);
        }

        $driver = new UsbDigitalPinDriver($context);
        $primary = new DigitalOutput(
            $this->pin,
            $this->consumer(),
            $driver,
            $this->default_state,
        );

        if (count($this->addl_pins) > 0) {
            return $this->buildMpssePinBus($driver, $primary);
        }

        return $primary;
    }

    public function defaultState(bool $state): static
    {
        $this->default_state = $state;

        return $this;
    }

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
}
