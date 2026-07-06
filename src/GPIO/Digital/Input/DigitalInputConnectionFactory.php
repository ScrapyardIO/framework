<?php

namespace GPIO\Digital\Input;

use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\Common\GeneralPurposeIO;
use GPIO\Contracts\Common\GPIOConnectionBus;
use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterInterface;
use GPIO\Contracts\Digital\DigitalInputConnectionFactory as DigitalInputConnectionFactoryContract;
use GPIO\Contracts\Digital\DigitalInputDriverAdapter as DigitalInputDriverAdapterInterface;
use GPIO\Contracts\Digital\DigitalPinException;
use GPIO\Contracts\Digital\LineBias;
use GPIO\Contracts\Digital\SignalDirection;
use GPIO\Digital\DigitalPinConnectionFactory;
use GPIO\Digital\MultipleDigitalPins;

#[CarrierFactory('digital-in')]
class DigitalInputConnectionFactory extends DigitalPinConnectionFactory implements DigitalInputConnectionFactoryContract
{
    /** @var DigitalInputDriverAdapterInterface */
    protected GPIODriverAdapterInterface $driver_adapter;

    public int $timeout_ms = 1000;

    public bool $active_low = false;

    public bool $rising_events = false;

    public bool $falling_events = false;

    public LineBias $line_bias = LineBias::AS_IS;

    public function __construct(string $driver)
    {
        parent::__construct(
            SignalDirection::INPUT,
            $driver
        );
    }

    public function activeLow(): static
    {
        $this->active_low = true;
        return $this;
    }

    public function lineBias(LineBias $line_bias): static
    {
        $this->line_bias = $line_bias;
        return $this;
    }

    public function withEvents(bool $rising, bool $falling): static
    {
        $this->rising_events = $rising;
        $this->falling_events = $falling;
        return $this;
    }

    public function timeout(int $timeout_ms): static
    {
        $this->timeout_ms = $timeout_ms;
        return $this;
    }

    /**
     * @throws DigitalPinException
     */
    public function create(): DigitalInput|MultipleDigitalPins
    {
        if(is_null($this->gpio_chip)) {
            throw DigitalPinException::missingDigitalPinDevice();
        }

        if(is_null($this->pin)) {
            throw DigitalPinException::missingDigitalPinOffset();
        }

        return $this->driver_adapter->buildConnection(
            $this->gpio_chip,
            $this->pin,
            $this->name,
            $this->addl_pins,
            $this->timeout_ms,
            $this->rising_events,
            $this->falling_events,
            $this->line_bias,
            $this->active_low
        );
    }
}
