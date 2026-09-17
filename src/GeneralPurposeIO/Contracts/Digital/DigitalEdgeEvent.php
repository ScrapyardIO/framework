<?php

namespace GeneralPurposeIO\Contracts\Digital;

final class DigitalEdgeEvent
{
    public function __construct(
        public SignalEdge $edge,
        public int|float $timestamp
    ) {}
}