<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use GeneralPurposeIO\Digital\DigitalInputTransport;

/** A pin with no stream, sampled on a timer like MPSSE: read() returns $levels in turn, the last one repeating. */
final class SampledDigitalInputTransport extends DigitalInputTransport
{
    /** @var list<bool> */
    public array $levels = [false];

    public int $samples = 0;

    private ?bool $sampled = null;

    private int $seqno = 0;

    public function __construct(int $pin, private readonly float $interval = 0.002)
    {
        parent::__construct($pin);
    }

    public function read(): bool
    {
        $this->ensureOpen();

        return $this->levels[min($this->samples++, count($this->levels) - 1)];
    }

    protected function drainEdges(): array
    {
        [$previous, $this->sampled] = [$this->sampled, $this->read()];

        if (is_null($previous) || $previous === $this->sampled) {
            return [];
        }

        return [new DigitalEdgeEvent($this->device, $this->pin, $this->sampled ? SignalEdge::RISING : SignalEdge::FALLING, hrtime(true), ++$this->seqno)];
    }

    protected function awaitEdges(int $timeout_ms): void
    {
        usleep((int) (1_000_000 * ($timeout_ms < 0 ? $this->interval : min($this->interval, $timeout_ms / 1000))));
    }

    protected function edgeStreams(): array
    {
        return [];
    }

    protected function samplingInterval(): ?float
    {
        return $this->interval;
    }

    protected function release(): void {}
}
