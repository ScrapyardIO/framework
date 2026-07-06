<?php

namespace GPIO\Contracts\Digital;

use GPIO\Contracts\Digital\DigitalPinConnectionFactory as DigitalPinConnectionFactoryContract;

interface DigitalOutputConnectionFactory extends DigitalPinConnectionFactoryContract
{
    public function defaultState(bool $state): static;
}
