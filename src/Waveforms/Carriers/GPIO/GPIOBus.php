<?php

namespace Waveforms\Carriers\GPIO;

abstract class GPIOBus
{
    abstract public function __call(string $name, array $arguments): GPIOPanel;
}
