<?php

namespace GPIO\Contracts\Digital;

use GPIO\Common\GPIO;
use GPIO\Contracts\Digital\DigitalPinConnectionFactory as DigitalPinConnectionFactoryInterface;

/**
 * @property string|int $driver
 */
trait DigitalPinsOnBus
{
    public ?int $gpio_chip = null;

    /** @var DigitalPinConnectionFactoryInterface[] $digital_pins */
    public array $digital_pins = [];

    public function digitalPins(?int $chip = null): static
    {
        $this->gpio_chip = $chip;
        return $this;
    }

    public function digitalIn(int $pin, string $name, array $events, int $timeout, LineBias $bias): static
    {
        $pin = GPIO::digitalIn($this->driver)
            ->pin($pin)
            ->name($name)
            ->withEvents(...$events)
            ->timeout($timeout)
            ->lineBias($bias);

        $this->digital_pins[] = $pin;
        return $this;
    }

    public function digitalOut(int $pin, string $name, bool $default_state): static
    {
        $pin = GPIO::digitalOut($this->driver)
            ->pin($pin)
            ->name($name)
            ->defaultState($default_state);

        $this->digital_pins[] = $pin;
        return $this;
    }
}
