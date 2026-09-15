<?php

namespace GeneralPurposeIO\Contracts\Digital;

use GeneralPurposeIO\Contracts\Digital\SignalEdge;

readonly class DigitalEdgeEvent
{
    public function __construct(
        public SignalEdge $edge,
        public int|float $timestamp
    ) {}
}
