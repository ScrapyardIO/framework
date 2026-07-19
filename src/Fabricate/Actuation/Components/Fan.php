<?php

namespace Fabricate\Actuation\Components;

use Fabricate\Actuation\ActuationComponent;
use Fabricate\Contracts\Actuation\Fans\AirBlower;

abstract class Fan extends ActuationComponent implements AirBlower
{
    abstract public function on(): void;

    abstract public function off(): void;
}
