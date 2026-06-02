<?php

namespace Waveforms\Factory;

abstract class CarrierFactory
{
    abstract public function connection(): string;
}
