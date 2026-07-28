<?php

namespace Fabricate\Contracts\Sensors\Measurements;

abstract class SensorEvent
{
    public function __construct(
        readonly int|float $timestamp
    ) {}
}
