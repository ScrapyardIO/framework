<?php

namespace GeneralPurposeIO\SPI\Factory;

use Fabricate\NutsAndBolts\MagicAliases\GPIO;
use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Contracts\SPI\SPIEndianness;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Contracts\SPI\SPIMode;
use GeneralPurposeIO\Digital\Factory\MpsseDigitalPinDriverFactory;
use GeneralPurposeIO\Digital\Factory\PosixDigitalPinDriverFactory;
use GeneralPurposeIO\SPI\SPI;
use GeneralPurposeIO\SPI\SPIConnectionBus;

abstract class SPIDriverFactory
{
    public int|string|null $device = null;

    public SPIMode $spi_mode = SPIMode::MODE_0;

    public int $chip_select = 0;

    public int $speed = 800_000;

    public int $bits_per_word = 8;

    public SPIEndianness $endianness = SPIEndianness::MSB;

    public ?int $gpio_chip = null;

    /** @var list<PosixDigitalPinDriverFactory|MpsseDigitalPinDriverFactory> */
    public array $digital_pins = [];

    abstract public function create(): SPI|SPIConnectionBus;

    abstract protected function carrierAdapter(): string;

    public function device(int|string $value): static
    {
        $this->device = $value;

        return $this;
    }

    public function mode(SPIMode $value): static
    {
        $this->spi_mode = $value;

        return $this;
    }

    public function speed(int $value): static
    {
        $this->speed = $value;

        return $this;
    }

    public function endianness(SPIEndianness $endianness): static
    {
        $this->endianness = $endianness;

        return $this;
    }

    public function chipSelect(int $value): static
    {
        $this->chip_select = $value;

        return $this;
    }

    public function bitsPerByte(int $value): static
    {
        $this->bits_per_word = $value;

        return $this;
    }

    public function digitalPins(?int $chip = null): static
    {
        $this->gpio_chip = $chip;

        return $this;
    }

    /**
     * @param  array{0: bool, 1: bool}  $events  [rising, falling]
     */
    public function digitalIn(
        int $pin,
        string $name,
        array $events,
        int $timeout,
        LineBias $bias = LineBias::AS_IS,
    ): static {
        $factory = GPIO::protocol('digital-in')
            ->adapter($this->carrierAdapter())
            ->pin($pin)
            ->name($name)
            ->withEvents($events[0] ?? false, $events[1] ?? false)
            ->timeout($timeout);

        if (method_exists($factory, 'lineBias')) {
            $factory->lineBias($bias);
        }

        $this->digital_pins[] = $factory;

        return $this;
    }

    public function digitalOut(int $pin, string $name, bool $default_state = false): static
    {
        $this->digital_pins[] = GPIO::protocol('digital-out')
            ->adapter($this->carrierAdapter())
            ->pin($pin)
            ->name($name)
            ->defaultState($default_state);

        return $this;
    }

    /**
     * @param  list<PosixDigitalPinDriverFactory|MpsseDigitalPinDriverFactory>  $digital_pins
     */
    public function createWith(array $digital_pins): SPIConnectionBus
    {
        $this->digital_pins = $digital_pins;

        /** @var SPIConnectionBus */
        return $this->create();
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
