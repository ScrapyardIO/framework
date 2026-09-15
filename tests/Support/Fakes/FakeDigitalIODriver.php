<?php

namespace ScrapyardIO\Tests\Support\Fakes;

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\DigitalIODriver;
use Microscrap\Bindings\GPIO\DataObjects\GPIOLineRequest;

final class FakeDigitalIODriver implements DigitalIODriver
{
    /** @var list<DigitalEdgeEvent> */
    public array $queued = [];

    public array $polls = [];

    public bool $level = false;

    public function read(GPIOLineRequest|int $pin): bool { return $this->level; }

    public function write(GPIOLineRequest|int $pin, bool $state): bool { return $this->level = $state; }

    public function listen(int $timeout, bool $rising_events, bool $falling_events, GPIOLineRequest|int $pin): ?DigitalEdgeEvent
    {
        return array_shift($this->queued);
    }

    public function pollEdges(GPIOLineRequest|int $pin, bool $rising_events, bool $falling_events, int $max_events = 16): array
    {
        $this->polls[] = [$pin, $rising_events, $falling_events, $max_events];
        $events = array_splice($this->queued, 0, $max_events);

        return $events;
    }

    public function close(): void {}
}
