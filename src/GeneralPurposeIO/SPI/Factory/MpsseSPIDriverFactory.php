<?php

namespace GeneralPurposeIO\SPI\Factory;

use GeneralPurposeIO\Contracts\Digital\DigitalPinException;
use GeneralPurposeIO\Contracts\SPI\SPIEndianness;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Contracts\SPI\SPIMode;
use GeneralPurposeIO\Digital\DigitalInput;
use GeneralPurposeIO\Digital\DigitalOutput;
use GeneralPurposeIO\Digital\Drivers\UsbDigitalPinDriver;
use GeneralPurposeIO\Digital\Factory\MpsseDigitalInputDriverFactory;
use GeneralPurposeIO\Digital\Factory\MpsseDigitalOutputDriverFactory;
use GeneralPurposeIO\Digital\Factory\MpsseDigitalPinDriverFactory;
use GeneralPurposeIO\SPI\Drivers\UsbSPIDriver;
use GeneralPurposeIO\SPI\SPI;
use GeneralPurposeIO\SPI\SPIConnectionBus;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;

class MpsseSPIDriverFactory extends SPIDriverFactory
{
    protected function carrierAdapter(): string
    {
        return 'usb';
    }

    /**
     * @throws SPIException
     * @throws DigitalPinException
     */
    public function create(): SPI|SPIConnectionBus
    {
        $this->assertReady();

        $error = '';
        $device = MpsseSupportedDevice::from($this->device);
        $interface = $device->interface();
        $context = mpsse_open(
            vid: FtdiVendorId::FTDI->value,
            pid: $device->productId(),
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
            throw SPIException::couldNotOpenMpsseContext($device->value, $error);
        }

        $driver = new UsbSPIDriver($context);
        $spi = new SPI($driver);

        if (count($this->digital_pins) === 0) {
            return $spi;
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

        return new SPIConnectionBus($spi, $pins, shares_spi_context: true);
    }
}
