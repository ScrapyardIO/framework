<?php

namespace GeneralPurposeIO\I2C\Factory;

use Fabricate\NutsAndBolts\MagicAliases\GPIO;
use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Contracts\I2C\I2CException;
use GeneralPurposeIO\Digital\Factory\PosixDigitalPinDriverFactory;
use GeneralPurposeIO\Digital\Factory\MpsseDigitalPinDriverFactory;
use GeneralPurposeIO\I2C\I2C;
use GeneralPurposeIO\I2C\I2CConnectionBus;

abstract class I2CDriverFactory
{
    public int|string|null $device = null;

    public ?int $slave_address = null;

    public ?int $gpio_chip = null;

    /** @var list<PosixDigitalPinDriverFactory|MpsseDigitalPinDriverFactory> */
    public array $digital_pins = [];

    abstract public function create(): I2C|I2CConnectionBus;

    abstract protected function carrierAdapter(): string;

    public function device(int|string $value): static
    {
        $this->device = $value;

        return $this;
    }

    /**
     * @throws I2CException
     */
    public function slave(int $value): static
    {
        if (($value > 0x07) && ($value <= 0x77)) {
            $this->slave_address = $value;

            return $this;
        }

        throw I2CException::invalidSlaveAddress($value);
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
    public function createWith(array $digital_pins): I2CConnectionBus
    {
        $this->digital_pins = $digital_pins;

        /** @var I2CConnectionBus */
        return $this->create();
    }

    /**
     * @throws I2CException
     */
    protected function assertReady(): void
    {
        if (is_null($this->device)) {
            throw I2CException::missingMasterDevice();
        }

        if (is_null($this->slave_address)) {
            throw I2CException::missingSlaveAddress();
        }
    }
}
