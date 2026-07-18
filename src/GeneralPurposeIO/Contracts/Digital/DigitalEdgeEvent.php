<?php

namespace GeneralPurposeIO\Contracts\Digital;

readonly class DigitalEdgeEvent
{
    public function __construct(
        public SignalEdge $edge,
        public int|float $timestamp
    ) {}
}
