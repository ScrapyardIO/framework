<?php

namespace Fabricate\Contracts\Actuation\Fans;

use Fabricate\Contracts\Actuation\ActuationComponent;

interface AirBlower extends ActuationComponent
{
    public function on(): void;

    public function off(): void;
}
