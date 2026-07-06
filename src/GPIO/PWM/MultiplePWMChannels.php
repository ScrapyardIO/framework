<?php

namespace GPIO\PWM;

use GPIO\Common\GPIOConnectionBus;
use GPIO\Contracts\Digital\DigitalPinBus;
use GPIO\Contracts\PWM\PWMChannelBus;
use GPIO\Digital\DigitalPin;

class MultiplePWMChannels extends GPIOConnectionBus implements PWMChannelBus
{
    /**
     * @param PWMChannel[] $channels
     */
    public function __construct(
        public readonly array $channels
    ) {}

    public function getChannel(string $name): ?PWMChannel
    {
        $results = null;

        if(isset($this->channels[$name])) {
            $results = $this->channels[$name];
        }

        return $results;
    }
}
