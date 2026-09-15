<?php

namespace GeneralPurposeIO\Contracts\Digital;

use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;

interface DigitalIODriver
{
    public function read(GPIOLineRequest|int $pin): bool;
    public function write(GPIOLineRequest|int $pin, bool $state): bool;
    public function listen(int $timeout, bool $rising_events, bool $falling_events, GPIOLineRequest|int $pin): ?DigitalEdgeEvent;

    /** @return list<DigitalEdgeEvent> edges buffered since the last poll; never waits */
    public function pollEdges(GPIOLineRequest|int $pin, bool $rising_events, bool $falling_events, int $max_events = 16): array;
    public function close(): void;
}