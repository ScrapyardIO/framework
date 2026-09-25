<?php

namespace GeneralPurposeIO\Contracts\Digital;

use Closure;
use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOTransport;

interface DigitalInTransport extends GPIOTransport
{
    public function read(): bool;

    /** Wire-internal: the connection driver names the device this pin rides on. */
    public function boundTo(string|int $device): static;

    /** Wire-internal: the connection driver hands over the closure that finds the event loop, or null. */
    public function resolvesLoopWith(Closure $resolver): static;

    /** @return list<DigitalEdgeEvent> every unread matching edge, oldest first; never waits */
    public function pollEdges(bool $rising_events, bool $falling_events): array;

    /** The oldest unread matching edge; waits up to $timeout ms (-1: no limit, 0: no wait). */
    public function listen(int $timeout, bool $rising_events, bool $falling_events): ?DigitalEdgeEvent;

    /** Mail a copy of every matching edge as gpio.edge.<device>.<pin>. Needs an event loop. */
    public function watch(bool $rising_events = true, bool $falling_events = true): void;

    public function unwatch(): void;
}