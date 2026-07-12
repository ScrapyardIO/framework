<?php

namespace BareMetal\Contracts\Actuators\Fans;

interface BasicFanFunctionality
{
    public function on(): void;

    public function off(): void;
}
