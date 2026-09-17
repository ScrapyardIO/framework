<?php

namespace GeneralPurposeIO\Contracts\Digital;

use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;

interface DigitalInTransport extends GPIOTransport
{
    public function read(): bool;
    public function pollEdges(bool $rising_events, bool $falling_events): array;
    public function listen(int $timeout, bool $rising_events, bool $falling_events): ?DigitalEdgeEvent;
}