<?php

namespace Waveforms\Carriers\GPIO\Events;

abstract class GPIOEdgeEvent
{
    public function __construct(
        public readonly int $timestamp_ns,
    ) {}
}
