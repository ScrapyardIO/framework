<?php

namespace BareMetal\Actuation\Fans;

use BareMetal\Contracts\Actuators\Fans\BasicFanFunctionality as FanInterface;

class BasicFan extends FanComponent
{
    public function __construct(
        protected FanInterface $fan,
    ) {}

    public function on(): void
    {
        $this->fan->on();
    }

    public function off(): void
    {
        $this->fan->off();
    }
}
