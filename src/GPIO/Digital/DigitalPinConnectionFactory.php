<?php

namespace GPIO\Digital;

use GPIO\Common\GPIOConnectionFactory;
use GPIO\Contracts\Common\GPIOConnectionBus;
use GPIO\Contracts\Digital\SignalDirection;
use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterInterface;
use GPIO\Contracts\Digital\DigitalPinDriverAdapter as DigitalPinDriverAdapterInterface;
use GPIO\Contracts\Digital\DigitalPinConnectionFactory as DigitalPinConnectionFactoryContract;

abstract class DigitalPinConnectionFactory extends GPIOConnectionFactory implements DigitalPinConnectionFactoryContract
{
    /** @var DigitalPinDriverAdapterInterface  */
    protected GPIODriverAdapterInterface $driver_adapter;

    public ?int $pin = null;

    public array $addl_pins = [];

    public int|string|null $gpio_chip = null;

    public ?string $name = 'scrapyard-io-gpio';

    public function __construct(
        protected readonly SignalDirection $direction,
        string $driver
    ) {
        parent::__construct($driver);
    }

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
        $this->gpio_chip = $value;
        return $this;
    }

    /**
     * @param int|string $device
     * @param DigitalPinConnectionFactory[] $addl_pins
     * @return GPIOConnectionBus
     */
    public function createWith(int|string $device, array $addl_pins): MultipleDigitalPins
    {
        $this->addl_pins = $addl_pins;
        return $this->device($device)->create();
    }
}
