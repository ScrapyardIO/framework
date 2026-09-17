<?php

namespace ScrapyardIO\Tests\Support\Fakes\Connections;

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\LineBias;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use GeneralPurposeIO\Digital\DigitalInputTransport;

class FakeDigitalInputTransport extends DigitalInputTransport
{
    public bool $level = false;

    /** @var list<DigitalEdgeEvent> edges waiting to be drained */
    public array $pending = [];

    public function __construct(
        int $pin,
        public readonly FakeHandle $handle,
        public readonly LineBias $bias,
        public readonly bool $active_low,
    ) {
        parent::__construct($pin);
    }

    public function read(): bool
    {
        return $this->level;
    }

    public function pollEdges(bool $rising_events, bool $falling_events): array
    {
        $drained = $this->pending;
        $this->pending = [];

        return array_values(array_filter(
            $drained,
            fn (DigitalEdgeEvent $event): bool => ($event->edge === SignalEdge::RISING && $rising_events)
                || ($event->edge === SignalEdge::FALLING && $falling_events),
        ));
    }

    public function listen(int $timeout, bool $rising_events, bool $falling_events): ?DigitalEdgeEvent
    {
        return $this->pollEdges($rising_events, $falling_events)[0] ?? null;
    }

    public function close(): void
    {
        $this->handle->closed = true;
    }
}
