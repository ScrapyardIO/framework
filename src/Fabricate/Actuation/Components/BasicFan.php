<?php

namespace Fabricate\Actuation\Components;

use Fabricate\Contracts\Actuation\ActuationException;
use Fabricate\Contracts\Actuation\Fans\BasicFanFunctionality;
use Fabricate\IntegratedCircuits\ActuatorIC;

class BasicFan extends Fan
{
    public function __construct(
        protected BasicFanFunctionality $fan,
    ) {}

    public function on(): void
    {
        $this->fan->on();
    }

    public function off(): void
    {
        $this->fan->off();
    }

    public static function buildWith(ActuatorIC $actuator): static
    {
        if ($actuator instanceof BasicFanFunctionality) {
            return new static($actuator);
        }

        throw new ActuationException($actuator::class.' must implement BasicFanFunctionality');
    }
}
