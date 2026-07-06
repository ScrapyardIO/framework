<?php

namespace GPIO\Digital\Output;

use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterInterface;
use GPIO\Contracts\Digital\DigitalOutputConnectionFactory as DigitalOutputConnectionFactoryContract;
use GPIO\Contracts\Digital\DigitalOutputDriverAdapter as DigitalOutputDriverAdapterInterface;
use GPIO\Contracts\Digital\DigitalPinException;
use GPIO\Contracts\Digital\SignalDirection;
use GPIO\Digital\DigitalPinConnectionFactory;
use GPIO\Digital\MultipleDigitalPins;

#[CarrierFactory('digital-out')]
class DigitalOutputConnectionFactory extends DigitalPinConnectionFactory implements DigitalOutputConnectionFactoryContract
{
    /** @var DigitalOutputDriverAdapterInterface  */
    protected GPIODriverAdapterInterface $driver_adapter;

    public bool $default_state = false;

    public function __construct(string $driver)
    {
        parent::__construct(
            SignalDirection::OUTPUT,
            $driver
        );
    }

    public function defaultState(bool $state): static
    {
        $this->default_state = $state;
        return $this;
    }

    /**
     * @throws DigitalPinException
     */
    public function create(): DigitalOutput|MultipleDigitalPins
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
            $this->default_state,
        );
    }
}
