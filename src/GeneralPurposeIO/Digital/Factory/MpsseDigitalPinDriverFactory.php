<?php

namespace GeneralPurposeIO\Digital\Factory;

use GeneralPurposeIO\Contracts\Digital\DigitalPinException;
use GeneralPurposeIO\Digital\DigitalInput;
use GeneralPurposeIO\Digital\DigitalOutput;
use GeneralPurposeIO\Digital\DigitalPin;
use GeneralPurposeIO\Digital\Drivers\UsbDigitalPinDriver;
use GeneralPurposeIO\Digital\MultipleDigitalPins;
use Microscrap\Bindings\MPSSE\MPSSEContext;

abstract class MpsseDigitalPinDriverFactory
{
    public ?int $pin = null;

    public int|string|null $device = null;

    public ?string $name = 'scrapyard-io-gpio';

    /** @var list<MpsseDigitalPinDriverFactory> */
    public array $addl_pins = [];

    abstract public function create(): DigitalPin|MultipleDigitalPins;

    public function pin(int $value): static
    {
        $this->pin = $value;

        return $this;
    }

    public function name(string $value): static
    {
        $this->name = $value;

        return $this;
    }

    public function device(int|string $value): static
    {
        $this->device = $value;

        return $this;
    }

    /**
     * @param  list<MpsseDigitalPinDriverFactory>  $addl_pins
     */
    public function createWith(int|string $device, array $addl_pins): MultipleDigitalPins
    {
        $this->addl_pins = $addl_pins;

        /** @var MultipleDigitalPins */
        return $this->device($device)->create();
    }

    public function consumer(): string
    {
        return $this->name ?? 'scrapyard-io-gpio';
    }

    /**
     * @throws DigitalPinException
     */
    protected function assertReady(): void
    {
        if (is_null($this->device)) {
            throw DigitalPinException::missingDigitalPinDevice();
        }

        if (is_null($this->pin)) {
            throw DigitalPinException::missingDigitalPinOffset();
        }
    }

    /**
     * Configure directions for every ride-along pin, then wrap them on one shared driver.
     *
     * @throws DigitalPinException
     */
    protected function configureMpsseAddlDirections(MPSSEContext $context): void
    {
        foreach ($this->addl_pins as $addl_pin) {
            if (is_null($addl_pin->pin)) {
                throw DigitalPinException::missingDigitalPinOffset();
            }

            mpsse_configure_pin_direction(
                $context,
                $addl_pin->pin,
                $addl_pin instanceof MpsseDigitalOutputDriverFactory,
            );
        }
    }

    /**
     * @throws DigitalPinException
     */
    protected function buildMpssePinBus(
        UsbDigitalPinDriver $driver,
        DigitalPin $primary,
    ): MultipleDigitalPins {
        $pins = [$this->consumer() => $primary];

        foreach ($this->addl_pins as $addl_pin) {
            $pins[$addl_pin->consumer()] = $this->instantiateMpssePin($addl_pin, $driver);
        }

        return new MultipleDigitalPins($pins);
    }

    protected function instantiateMpssePin(
        MpsseDigitalPinDriverFactory $factory,
        UsbDigitalPinDriver $driver,
    ): DigitalPin {
        if ($factory instanceof MpsseDigitalOutputDriverFactory) {
            return new DigitalOutput(
                $factory->pin,
                $factory->consumer(),
                $driver,
                $factory->default_state,
            );
        }

        if ($factory instanceof MpsseDigitalInputDriverFactory) {
            return new DigitalInput(
                $factory->pin,
                $factory->consumer(),
                $driver,
                $factory->timeout_ms,
                $factory->rising_events,
                $factory->falling_events,
            );
        }

        throw new DigitalPinException('Unsupported MPSSE digital pin factory for pin bus.');
    }
}
