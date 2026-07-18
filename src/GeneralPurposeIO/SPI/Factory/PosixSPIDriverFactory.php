<?php

namespace GeneralPurposeIO\SPI\Factory;

use GeneralPurposeIO\Contracts\SPI\SPIEndianness;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Digital\Factory\PosixDigitalPinDriverFactory;
use GeneralPurposeIO\SPI\Drivers\PosixSPIDriver;
use GeneralPurposeIO\SPI\SPI;
use GeneralPurposeIO\SPI\SPIConnectionBus;
use Microscrap\Bindings\SPI\Enums\SPIMode as NativeSPIMode;

class PosixSPIDriverFactory extends SPIDriverFactory
{
    protected function carrierAdapter(): string
    {
        return 'posix';
    }

    /**
     * @throws SPIException
     */
    public function create(): SPI|SPIConnectionBus
    {
        $this->assertReady();

        $mode = $this->spi_mode->value;
        if ($this->endianness === SPIEndianness::LSB) {
            $mode |= NativeSPIMode::LSB_FIRST->value;
        }

        $path = "/dev/spidev{$this->device}.{$this->chip_select}";
        $spi_device = spi_open($path, $mode, $this->speed, $this->bits_per_word);

        if (is_null($spi_device)) {
            throw SPIException::couldNotOpenSPIDevice($this->device, $this->chip_select);
        }

        $driver = new PosixSPIDriver($spi_device);
        $spi = new SPI($driver);

        if (count($this->digital_pins) === 0) {
            return $spi;
        }

        if (is_null($this->gpio_chip)) {
            throw SPIException::missingGpioChipForDigitalPins();
        }

        /** @var list<PosixDigitalPinDriverFactory> $factories */
        $factories = $this->digital_pins;

        $pin_bus = PosixDigitalPinDriverFactory::bundle(
            (int) $this->gpio_chip,
            'spi-gpio-pins',
            $factories,
        );

        return new SPIConnectionBus($spi, $pin_bus->pins, shares_spi_context: false);
    }
}
