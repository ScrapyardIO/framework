<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use GeneralPurposeIO\Digital\DigitalInputTransport;

/** A pin whose edge queue is one end of a socket pair: emit() plays the kernel, one byte per edge ('r' rising, 'f' falling). */
final class SocketDigitalInputTransport extends DigitalInputTransport
{
    /** @var resource */
    public $reader;

    /** @var resource */
    public $writer;

    public bool $released = false;

    private int $seqno = 0;

    public function __construct(int $pin)
    {
        parent::__construct($pin);

        [$this->reader, $this->writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_blocking($this->reader, false);
    }

    public function emit(string $edges): void
    {
        fwrite($this->writer, $edges);
    }

    public function read(): bool
    {
        $this->ensureOpen();

        return false;
    }

    protected function drainEdges(): array
    {
        $edges = [];

        while (is_string($bytes = fread($this->reader, 64)) && $bytes !== '') {
            foreach (str_split($bytes) as $byte) {
                $edges[] = new DigitalEdgeEvent(
                    $this->device,
                    $this->pin,
                    $byte === 'r' ? SignalEdge::RISING : SignalEdge::FALLING,
                    hrtime(true),
                    ++$this->seqno,
                );
            }
        }

        return $edges;
    }

    protected function awaitEdges(int $timeout_ms): void
    {
        $read = [$this->reader];
        $write = $except = null;

        $timeout_ms < 0
            ? stream_select($read, $write, $except, null)
            : stream_select($read, $write, $except, intdiv($timeout_ms, 1000), ($timeout_ms % 1000) * 1000);
    }

    protected function edgeStreams(): array
    {
        return [$this->reader];
    }

    protected function samplingInterval(): ?float
    {
        return null;
    }

    protected function release(): void
    {
        fclose($this->reader);
        fclose($this->writer);
        $this->released = true;
    }
}
