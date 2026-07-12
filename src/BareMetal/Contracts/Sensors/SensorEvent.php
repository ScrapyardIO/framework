<?php

namespace BareMetal\Contracts\Sensors;

abstract class SensorEvent
{
    public function __construct(
        readonly int|float $timestamp
    ) {}
}
