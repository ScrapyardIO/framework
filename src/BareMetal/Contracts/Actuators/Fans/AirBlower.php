<?php

namespace BareMetal\Contracts\Actuators\Fans;

use BareMetal\Contracts\Actuators\ActuationComponent;

interface AirBlower extends ActuationComponent
{
    public function on(): void;
    public function off(): void;
}
