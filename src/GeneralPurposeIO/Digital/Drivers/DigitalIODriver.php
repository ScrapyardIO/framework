<?php

namespace GeneralPurposeIO\Digital\Drivers;

use GeneralPurposeIO\Contracts\Digital\DigitalIODriver as DriverContract;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;

abstract class DigitalIODriver implements DriverContract
{
    abstract public function read(GPIOLineRequest|int $pin): bool;
    abstract public function write(GPIOLineRequest|int $pin, bool $state): bool;
    abstract public function listen(int $timeout, bool $rising_events, bool $falling_events, GPIOLineRequest|int $pin): ?DigitalEdgeEvent;

    /** @return list<DigitalEdgeEvent> edges buffered since the last poll; never waits */
    abstract public function pollEdges(GPIOLineRequest|int $pin, bool $rising_events, bool $falling_events, int $max_events = 16): array;
    abstract public function close(): void;
}