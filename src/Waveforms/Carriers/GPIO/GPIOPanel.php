<?php

namespace Waveforms\Carriers\GPIO;

use Waveforms\Carriers\GPIO\Events\GPIOEdgeEvent;
use Waveforms\WaveformCarrier;

abstract class GPIOPanel extends WaveformCarrier
{
    abstract public function high(): int;

    abstract public function low(): int;

    abstract public function read(): int;

    abstract public function listen(): ?GPIOEdgeEvent;
}
