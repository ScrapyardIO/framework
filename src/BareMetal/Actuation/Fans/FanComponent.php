<?php

namespace BareMetal\Actuation\Fans;

use BareMetal\Actuation\ActuationComponent;
use BareMetal\Contracts\Actuators\Fans\AirBlower;

abstract class FanComponent extends ActuationComponent implements AirBlower
{
    abstract public function on(): void;
    abstract public function off(): void;
}
