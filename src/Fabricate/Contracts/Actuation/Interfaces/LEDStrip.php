<?php

namespace Fabricate\Contracts\Actuation\Interfaces;

interface LEDStrip extends LEDShape
{
    public function length(): int;
}
