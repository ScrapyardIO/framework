<?php

namespace Waveforms\Carriers\GPIO\Concerns;

trait NonblockingGPIO
{
    public bool $nonblocking = false;

    public function nonblocking(): static
    {
        $this->nonblocking = true;

        return $this;
    }
}
