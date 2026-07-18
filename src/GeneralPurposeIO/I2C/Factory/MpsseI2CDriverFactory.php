<?php

namespace GeneralPurposeIO\I2C\Factory;

use GeneralPurposeIO\Contracts\Digital\DigitalPinException;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Digital\DigitalInput;
use GeneralPurposeIO\Digital\DigitalOutput;
use GeneralPurposeIO\Digital\Drivers\UsbDigitalPinDriver;
use GeneralPurposeIO\Digital\Factory\MpsseDigitalInputDriverFactory;
use GeneralPurposeIO\Digital\Factory\MpsseDigitalOutputDriverFactory;
use GeneralPurposeIO\Digital\Factory\MpsseDigitalPinDriverFactory;
use GeneralPurposeIO\I2C\Drivers\UsbI2CDriver;
use GeneralPurposeIO\I2C\I2C;
use GeneralPurposeIO\I2C\I2CConnectionBus;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;
use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;

class MpsseI2CDriverFactory extends I2CDriverFactory
{
    protected MPSSEEndianness $endianness = MPSSEEndianness::MSB;

    protected MPSSEClockRate $clock_rate = MPSSEClockRate::ONE_MHZ;

    protected function carrierAdapter(): string
    {
        return 'usb';
    }

    /**
     * @throws I2CException
     */
    public function create(): I2C|I2CConnectionBus
    {
        $this->assertReady();

        $error = '';
        $device = MpsseSupportedDevice::from($this->device);
        $interface = $device->interface();
        $context = mpsse_open(
            vid: FtdiVendorId::FTDI->value,
            pid: $device->productId(),
            mode: MPSSEMode::I2C,
            freq: $this->clock_rate->value,
            endianness: $this->endianness,
            iface: $interface,
            error: $error,
        );

        if (! empty($error) || is_null($context)) {
            throw I2CException::couldNotOpenMpsseContext($device->value, $error);
        }

        $driver = new UsbI2CDriver($context, $this->slave_address);
        $i2c = new I2C($driver, $this->slave_address);

        if (count($this->digital_pins) === 0) {
            return $i2c;
        }

        $pin_driver = new UsbDigitalPinDriver($context);
        $pins = [];

        foreach ($this->digital_pins as $pin_factory) {
            /** @var MpsseDigitalPinDriverFactory $pin_factory */
            if (is_null($pin_factory->pin)) {
                throw DigitalPinException::missingDigitalPinOffset();
            }

            mpsse_configure_pin_direction(
                $context,
                $pin_factory->pin,
                $pin_factory instanceof MpsseDigitalOutputDriverFactory,
            );

            $pins[$pin_factory->consumer()] = $pin_factory instanceof MpsseDigitalOutputDriverFactory
                ? new DigitalOutput(
                    $pin_factory->pin,
                    $pin_factory->consumer(),
                    $pin_driver,
                    $pin_factory->default_state,
                )
                : new DigitalInput(
                    $pin_factory->pin,
                    $pin_factory->consumer(),
                    $pin_driver,
                    $pin_factory instanceof MpsseDigitalInputDriverFactory ? $pin_factory->timeout_ms : 1000,
                    $pin_factory instanceof MpsseDigitalInputDriverFactory ? $pin_factory->rising_events : false,
                    $pin_factory instanceof MpsseDigitalInputDriverFactory ? $pin_factory->falling_events : false,
                );
        }

        return new I2CConnectionBus($i2c, $pins, shares_i2c_context: true);
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
