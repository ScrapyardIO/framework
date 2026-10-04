<?php

namespace ScrapyardIO\Tests\Fixtures;

use GeneralPurposeIO\Contracts\UART\UARTException;
use GeneralPurposeIO\UART\UARTTransport;

/** A port whose far end is the other half of a socket pair: the test plays the device on $wire. */
final class FakeUARTTransport extends UARTTransport
{
    /** @var resource the port's end */
    public $port;

    /** @var resource the device's end: what the test writes here arrives at the port, what the port sends lands here */
    public $wire;

    /** Bytes the port still takes before it reports no room; a test shrinks it to play a full transmit queue. */
    public int $room = PHP_INT_MAX;

    /** A broken port refuses every hand-off. */
    public bool $broken = false;

    /** @var list<int> the size of every hand-off, in order */
    public array $chunks = [];

    /** @var array<string, bool> the last state set on each modem line */
    public array $lines = [];

    public bool $purged = false;

    public bool $released = false;

    /** Seconds between samples; null: the socket wakes the loop. A sampled fake plays an FTDI port. */
    public ?float $sample_every = null;

    /** How many drains were made. */
    public int $drains = 0;

    /** Drains that still hand back a byte each: plays a device that never goes quiet. */
    public int $endless = 0;

    /** Every drain fails: plays a device that went away. */
    public bool $failing = false;

    public function __construct(
        string $device,
        int $baud,
        public readonly FakeUARTHandle $handle,
    ) {
        parent::__construct($device, $baud);

        [$this->port, $this->wire] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_blocking($this->port, false);
    }

    public function handle(): FakeUARTHandle
    {
        return $this->handle;
    }

    public function path(): string
    {
        return "fake:{$this->device}";
    }

    /** What the device end has received so far; never waits. */
    public function sent(): string
    {
        stream_set_blocking($this->wire, false);
        $bytes = (string) stream_get_contents($this->wire);
        stream_set_blocking($this->wire, true);

        return $bytes;
    }

    public function dtr(bool $asserted): void
    {
        $this->ensureOpen();
        $this->lines['dtr'] = $asserted;
    }

    public function rts(bool $asserted): void
    {
        $this->ensureOpen();
        $this->lines['rts'] = $asserted;
    }

    protected function drainBytes(): string
    {
        $this->drains++;

        if ($this->failing) {
            throw UARTException::readFailed($this->device);
        }

        if ($this->endless > 0) {
            $this->endless--;

            return 'x';
        }

        // everything the socket holds, as a tty hands over its whole queue in one read
        $bytes = fread($this->port, 262_144);

        return $bytes === false ? throw UARTException::readFailed($this->device) : $bytes;
    }

    protected function awaitBytes(int $timeout_ms): void
    {
        $read = [$this->port];
        $write = $except = null;

        $timeout_ms < 0
            ? stream_select($read, $write, $except, null)
            : stream_select($read, $write, $except, intdiv($timeout_ms, 1000), ($timeout_ms % 1000) * 1000);
    }

    protected function roomNow(): bool
    {
        return $this->room > 0;
    }

    protected function awaitRoom(int $timeout_ms): void
    {
        usleep(1_000 * ($timeout_ms < 0 ? 5 : min($timeout_ms, 5)));
    }

    protected function transmit(string $bytes): int
    {
        if ($this->broken) {
            return -1;
        }

        $taken = substr($bytes, 0, $this->room);
        fwrite($this->port, $taken);
        $this->room -= strlen($taken);
        $this->chunks[] = strlen($taken);

        return strlen($taken);
    }

    protected function purge(): void
    {
        $this->purged = true;

        while (! in_array(fread($this->port, 4096), ['', false], true));
    }

    protected function release(): void
    {
        fclose($this->port);
        fclose($this->wire);
        $this->handle->closed = true;
        $this->released = true;
    }

    protected function intakeStreams(): array
    {
        return is_null($this->sample_every) ? [$this->port] : [];
    }

    protected function samplingInterval(): ?float
    {
        return $this->sample_every;
    }
}
