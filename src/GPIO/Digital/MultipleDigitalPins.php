<?php

namespace GPIO\Digital;

use GPIO\Common\GPIOConnectionBus;
use GPIO\Contracts\Digital\DigitalPinBus;

class MultipleDigitalPins extends GPIOConnectionBus implements DigitalPinBus
{
    /**
     * @param DigitalPin[] $pins
     */
    public function __construct(
        public readonly array $pins
    ) {}

    public function getPin(string $name): ?DigitalPin
    {
        $results = null;

        if(isset($this->pins[$name])) {
            $results = $this->pins[$name];
        }

        return $results;
    }
}
