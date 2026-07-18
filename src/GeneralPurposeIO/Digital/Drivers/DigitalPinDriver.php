<?php

namespace GeneralPurposeIO\Digital\Drivers;

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;

abstract class DigitalPinDriver
{
    abstract public function close(): void;
    abstract public function read(int $pin): bool;
    abstract public function write(int $pin, bool $state): bool;
    abstract public function listen(int $timeout, bool $rising_events, bool $falling_events, int $pin): ?DigitalEdgeEvent;
}
