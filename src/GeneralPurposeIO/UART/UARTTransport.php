<?php

namespace GeneralPurposeIO\UART;

use Closure;
use GeneralPurposeIO\Contracts\UART\UARTException;
use GeneralPurposeIO\Contracts\UART\UARTReceived;
use GeneralPurposeIO\Contracts\UART\UARTTransport as TransportContract;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\LoopTimer;

/**
 * One serial port. Every byte the adapter hands over lands in one unread buffer: read(), readUntil() and pollBytes()
 * take from it, and watch() also mails a copy of each chunk. Every wait carries the caller's timeout. With no loop
 * bound it sleeps in the adapter; with one, a fiber suspends and the main stack keeps the loop turning. The port sits
 * on the loop exactly while it is watched or a call waits on it.
 */
abstract class UARTTransport implements TransportContract
{
    /** Unread bytes kept per port; past it the oldest go, counted by dropped(). */
    public const int BUFFER_BYTES = 65_536;

    /** At most this many bytes per hand-off: a tty reports room once fewer than 256 are queued, so 256 always fit. */
    public const int TX_CHUNK = 256;

    private string $unread = '';

    private int $dropped = 0;

    private int $received = 0;

    private bool $closed = false;

    /** What the loop's intake failed with: the port is done, and whoever waits on it gets this. */
    private ?UARTException $failure = null;

    /** @var list<object> one token per write() in arrival order; only the first transmits */
    private array $writers = [];

    private bool $watching = false;

    private int $waiters = 0;

    private ?Closure $loop_resolver = null;

    private ?Loop $registered_on = null;

    private ?LoopTimer $sampler = null;

    private readonly PortWatch $watch;

    public function __construct(
        public readonly string $device,
        public readonly int $baud,
    ) {
        $this->watch = new PortWatch(fn (): array => $this->intakeStreams(), fn () => $this->intake());
    }

    abstract public function handle(): mixed;

    /** What the port holds now, possibly nothing; never waits. Throws when the port fails. */
    abstract protected function drainBytes(): string;

    /** No loop: sleeps until bytes may be waiting or $timeout_ms passes (-1: no limit). */
    abstract protected function awaitBytes(int $timeout_ms): void;

    /** Whether the port takes TX_CHUNK more bytes without blocking. */
    abstract protected function roomNow(): bool;

    /** No loop: sleeps until roomNow() may hold or $timeout_ms passes (-1: no limit). */
    abstract protected function awaitRoom(int $timeout_ms): void;

    /** Hands up to TX_CHUNK bytes to the OS; how many it took, or -1 on failure. Called only once roomNow() holds. */
    abstract protected function transmit(string $bytes): int;

    /** Discards what the port holds in both directions. */
    abstract protected function purge(): void;

    /** Gives the port back to the OS. */
    abstract protected function release(): void;

    /** @return list<resource> what the loop selects on for incoming bytes; empty when a timer samples the port */
    abstract protected function intakeStreams(): array;

    /** Seconds between samples for a port with no stream; null when intakeStreams() wakes the loop. */
    abstract protected function samplingInterval(): ?float;

    public function closed(): bool
    {
        return $this->closed;
    }

    public function dropped(): int
    {
        return $this->dropped;
    }

    public function name(): string
    {
        return "gpio.uart.{$this->device}";
    }

    /** Wire-internal: the connection driver hands every port the closure that finds the event loop. */
    public function resolvesLoopWith(Closure $resolver): static
    {
        $this->loop_resolver = $resolver;

        return $this;
    }

    public function read(int $length, int $timeout_ms = -1): array
    {
        $this->waitFor(fn (): bool => $this->unread !== '', $timeout_ms, fn (int $ms) => $this->awaitBytes($ms));

        return bytes2array($this->take($length));
    }

    public function readUntil(string $delimiter, int $timeout_ms = -1): ?string
    {
        $this->ensureOpen();

        if ($delimiter === '') {
            throw UARTException::emptyDelimiter();
        }

        $found = $this->waitFor(fn (): bool => str_contains($this->unread, $delimiter), $timeout_ms, fn (int $ms) => $this->awaitBytes($ms));

        return $found ? $this->take(strpos($this->unread, $delimiter) + strlen($delimiter)) : null;
    }

    public function pollBytes(int $max_bytes = 4096): string
    {
        $this->collect();

        return $this->take($max_bytes);
    }

    public function write(array|string $data, int $timeout_ms = -1): int
    {
        $this->ensureOpen();

        $bytes = is_array($data) ? array2bytes($data) : $data;
        $total = strlen($bytes);
        $deadline = self::deadline($timeout_ms);
        $turn = new \stdClass;
        $this->writers[] = $turn;

        try {
            // writes go out whole and in arrival order: a second writer waits for the first to finish
            $mine = $this->waitFor(
                fn (): bool => $this->writers[0] === $turn,
                self::remaining($deadline),
                fn (int $ms) => $this->awaitRoom($ms),
                $this->chunkAirtime(),
            );

            if (! $mine) {
                throw UARTException::writeTimedOut($this->device, 0, $total);
            }

            for ($sent = 0; $sent < $total; $sent += $taken) {
                $room = $this->waitFor(
                    fn (): bool => $this->roomNow(),
                    self::remaining($deadline),
                    fn (int $ms) => $this->awaitRoom($ms),
                    $this->chunkAirtime(),
                );

                if (! $room) {
                    throw UARTException::writeTimedOut($this->device, $sent, $total);
                }

                $taken = $this->transmit(substr($bytes, $sent, self::TX_CHUNK));

                if ($taken < 0) {
                    throw UARTException::writeFailed($this->device, $sent, $total);
                }
            }

            return $total;
        } finally {
            $this->writers = array_values(array_filter($this->writers, fn (object $writer): bool => $writer !== $turn));
        }
    }

    public function flush(): void
    {
        $this->ensureOpen();
        $this->purge();
        $this->unread = '';
    }

    public function watch(): void
    {
        $this->ensureOpen();

        if (is_null($this->eventLoop())) {
            throw UARTException::watchNeedsLoop();
        }

        $this->watching = true;
        $this->settle();
    }

    public function unwatch(): void
    {
        $this->watching = false;
        $this->settle();
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        // a waiter learns of the close on a later turn's resume: keep one due, so run() cannot end before it does
        if ($this->waiters > 0) {
            $this->registered_on?->at(0.0, static fn () => null);
        }

        [$this->closed, $this->watching] = [true, false];
        $this->settle();
        $this->release();
    }

    /**
     * @throws UARTException
     */
    protected function ensureOpen(): void
    {
        if (! is_null($this->failure)) {
            throw $this->failure;
        }

        if ($this->closed) {
            throw UARTException::portClosed($this->device);
        }
    }

    /**
     * Moves one drain of what the adapter holds into the unread buffer, mailing it while watched and dropping the
     * oldest past BUFFER_BYTES. One drain a pass: a device that never goes quiet cannot hold the caller or the loop.
     */
    protected function collect(): void
    {
        $this->ensureOpen();

        $bytes = $this->drainBytes();

        if ($bytes === '') {
            return;
        }

        $this->unread .= $bytes;

        if ($this->watching) {
            $this->watch->post(new UARTReceived($this->device, $bytes, hrtime(true), ++$this->received));
        }

        $over = strlen($this->unread) - self::BUFFER_BYTES;

        if ($over > 0) {
            $this->unread = substr($this->unread, $over);
            $this->dropped += $over;
        }
    }

    /** The loop's intake: a failure goes to whoever waits on the port, the port leaves the loop, and with no waiter the loop hears it. */
    private function intake(): void
    {
        try {
            $this->collect();
        } catch (UARTException $e) {
            [$this->failure, $this->watching] = [$e, false];
            $this->settle();

            if ($this->waiters === 0) {
                throw $e;
            }
        }
    }

    /**
     * Returns once $ready holds (true) or $timeout_ms passes (false). $pace polls $ready on a timer while a loop
     * waits: set for room, which the loop's select cannot watch, and null for bytes, which the intake wakes it for.
     */
    private function waitFor(Closure $ready, int $timeout_ms, Closure $sleep, ?float $pace = null): bool
    {
        $this->collect();

        if ($ready()) {
            return true;
        }

        $loop = $this->eventLoop();

        return is_null($loop) || $timeout_ms === 0
            ? $this->waitBlocking($ready, $timeout_ms, $sleep)
            : $this->waitOn($loop, $ready, $timeout_ms, $pace);
    }

    private function waitBlocking(Closure $ready, int $timeout_ms, Closure $sleep): bool
    {
        $deadline = self::deadline($timeout_ms);

        while (! $ready()) {
            if (! is_null($deadline) && hrtime(true) >= $deadline) {
                return false;
            }

            $sleep(self::remaining($deadline));
            $this->collect();
        }

        return true;
    }

    private function waitOn(Loop $loop, Closure $ready, int $timeout_ms, ?float $pace): bool
    {
        $expired = false;
        $timer = $timeout_ms > 0 ? $loop->at($timeout_ms / 1000, function () use (&$expired) { $expired = true; }) : null;
        $poll = is_null($pace) ? null : $loop->every($pace, static fn () => null, "{$this->name()}.room");

        $this->waiters++;
        $this->settle();

        try {
            $loop->until(function () use ($ready, &$expired): bool {
                return $this->closed || ! is_null($this->failure) || $expired || $ready();
            });
        } finally {
            $timer?->cancel();
            $poll?->cancel();
            $this->waiters--;
            $this->settle();
        }

        $this->ensureOpen();

        return $ready();
    }

    /** On the loop exactly while watched or waited on, never once closed; a sampled port's timer lives just as long. */
    private function settle(): void
    {
        $wanted = ! $this->closed && is_null($this->failure) && ($this->watching || $this->waiters > 0);

        if (! $wanted) {
            if (is_null($this->registered_on)) {
                return;
            }

            $this->sampler?->cancel();
            $this->registered_on->forget($this->name());
            [$this->registered_on, $this->sampler] = [null, null];

            return;
        }

        if (! is_null($this->registered_on)) {
            return;
        }

        $this->registered_on = $this->eventLoop();
        $this->registered_on->resource($this->name(), $this->watch);

        $interval = $this->samplingInterval();
        $this->sampler = is_null($interval) ? null : $this->registered_on->every($interval, fn () => $this->intake(), $this->name());
    }

    /** How long TX_CHUNK bytes take on the wire at 10 bits a byte, between 1 ms and 100 ms: the pace a loop polls for room at. */
    private function chunkAirtime(): float
    {
        return min(0.1, max(0.001, self::TX_CHUNK * 10 / max($this->baud, 1)));
    }

    private function take(int $length): string
    {
        $length = max(0, $length);
        [$taken, $this->unread] = [substr($this->unread, 0, $length), substr($this->unread, $length)];

        return $taken;
    }

    private function eventLoop(): ?Loop
    {
        return is_null($this->loop_resolver) ? null : ($this->loop_resolver)();
    }

    /** hrtime() once $timeout_ms has passed; null for no limit. */
    private static function deadline(int $timeout_ms): ?int
    {
        return $timeout_ms < 0 ? null : hrtime(true) + $timeout_ms * 1_000_000;
    }

    /** Milliseconds left before $deadline, rounded up so a wait never becomes a poll (-1: no limit, 0: passed). */
    private static function remaining(?int $deadline): int
    {
        return is_null($deadline) ? -1 : (int) ceil(max(0, $deadline - hrtime(true)) / 1_000_000);
    }
}
