<?php

namespace Waveforms\Carriers\GPIO\Contracts;

interface GPIOInput
{
    public function edgeEvents(): static;

    public function nonblocking(): static;
}
