<?php

namespace GeneralPurposeIO\Digital;

use Closure;
use GeneralPurposeIO\Contracts\Core\EdgeSource;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\DigitalIOException;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use GeneralPurposeIO\NutsAndBolts\CarrierTransport;
use GeneralPurposeIO\Contracts\Digital\DigitalInTransport as TransportContract;
use Voyager\Contracts\IOPools\Loop as LoopInterface;
use Voyager\Contracts\IOPools\LoopTimer;

/**
 * Every edge the hardware reports lands in one queue of unread edges, whoever asked for it.
 * listen() and pollEdges() take from that queue; watch() also mails a copy of each edge.
 * The pin sits on the loop exactly while it is watched or a listen() waits on it.
 */
abstract class DigitalInputTransport extends CarrierTransport implements TransportContract, EdgeSource
{
    /**
     * Unread edges kept per pin, oldest dropped past it. The Linux line request asks the kernel for the same depth.
     */
    public const int QUEUE_DEPTH = 64;

    protected string|int|null $device = null;

    /** @var list<DigitalEdgeEvent> */
    private array $unread = [];

    private ?EdgeWatch $watch = null;
    private ?Closure $loop_resolver = null;
    private ?LoopInterface $registered_on = null;
    private ?LoopTimer $sampler = null;
    private bool $watching = false;
    private bool $mail_rising = true;
    private bool $mail_falling = true;
    private int $listeners = 0;
    private bool $closed = false;

    public function __construct(
        public readonly int $pin,
    ) {
        $this->watch = new EdgeWatch(fn (): array => $this->edgeStreams(), fn () => $this->collect());
    }

    /** @return list<DigitalEdgeEvent> every edge the hardware holds now, oldest first; never waits */
    abstract protected function drainEdges(): array;

    /** No-loop wait: return once edges may be ready or $timeout_ms passed (-1: no limit). */
    abstract protected function awaitEdges(int $timeout_ms): void;

    /** @return list<resource> what the loop selects on; empty when a timer samples the pin */
    abstract protected function edgeStreams(): array;

    /** Seconds between samples for a pin with no stream; null when edgeStreams() wakes the loop. */
    abstract protected function samplingInterval(): ?float;

    /** Give back what this pin alone holds; the connection stays open. */
    abstract protected function release(): void;

    public function offset(): int
    {
        return $this->pin;
    }

    public function device(): string|int|null
    {
        return $this->device;
    }

    public function closed(): bool
    {
        return $this->closed;
    }

    /** Wire-internal: the connection driver names the device this pin rides on. */
    public function boundTo(string|int $device): static
    {
        $this->device = $device;

        return $this;
    }

    public function resolvesLoopWith(Closure $resolver): static
    {
        $this->loop_resolver = $resolver;

        return $this;
    }

    public function name(): string
    {
        return "gpio.edge.{$this->device}.{$this->pin}";
    }

    public function pollEdges(bool $rising_events, bool $falling_events): array
    {
        $this->collect();

        $edges = [];
        while ($edge = $this->take($rising_events, $falling_events)) {
            $edges[] = $edge;
        }

        return $edges;
    }

    public function listen(int $timeout, bool $rising_events, bool $falling_events): ?DigitalEdgeEvent
    {
        $this->collect();

        if (($edge = $this->take($rising_events, $falling_events)) || $timeout === 0) {
            return $edge;
        }

        $loop = $this->eventLoop();

        return is_null($loop)
            ? $this->listenBlocking($timeout, $rising_events, $falling_events)
            : $this->listenOn($loop, $timeout, $rising_events, $falling_events);
    }

    public function watch(bool $rising_events = true, bool $falling_events = true): void
    {
        $this->ensureOpen();

        if (is_null($this->eventLoop())) {
            throw DigitalIOException::noEventLoop();
        }

        [$this->watching, $this->mail_rising, $this->mail_falling] = [true, $rising_events, $falling_events];
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

        [$this->closed, $this->watching] = [true, false];
        $this->settle();
        $this->release();
    }

    protected function ensureOpen(): void
    {
        if ($this->closed) {
            throw DigitalIOException::pinClosed($this->pin, $this->device);
        }
    }

    protected function collect(): void
    {
        $this->ensureOpen();

        foreach ($this->drainEdges() as $edge) {
            $this->unread[] = $edge;

            if (count($this->unread) > self::QUEUE_DEPTH) {
                array_shift($this->unread);
            }

            if ($this->watching && $this->passes($edge, $this->mail_rising, $this->mail_falling)) {
                $this->watch->post($edge);
            }
        }
    }

    /** On the loop exactly while watched or listened to, never once closed; a sampled pin's timer follows its interval. */
    protected function settle(): void
    {
        $wanted = ! $this->closed && ($this->watching || $this->listeners > 0);

        if (! $wanted) {
            if (is_null($this->registered_on)) {
                return;
            }

            $this->sampler?->cancel();
            $this->registered_on->forget($this->name());
            [$this->registered_on, $this->sampler] = [null, null];

            return;
        }

        if (is_null($this->registered_on)) {
            $this->registered_on = $this->eventLoop();
            $this->registered_on->resource($this->name(), $this->watch);
        }

        $interval = $this->samplingInterval();

        if ($this->sampler?->interval() !== $interval) {
            $this->sampler?->cancel();
            $this->sampler = is_null($interval)
                ? null
                : $this->registered_on->every($interval, fn () => $this->collect(), $this->name());
        }
    }

    private function listenBlocking(int $timeout, bool $rising, bool $falling): ?DigitalEdgeEvent
    {
        $deadline = $timeout < 0 ? null : hrtime(true) + $timeout * 1_000_000;

        while (true) {
            $this->awaitEdges(is_null($deadline) ? -1 : (int) ceil(max(0, $deadline - hrtime(true)) / 1_000_000));
            $this->collect();

            if ($edge = $this->take($rising, $falling)) {
                return $edge;
            }

            if (! is_null($deadline) && hrtime(true) >= $deadline) {
                return null;
            }
        }
    }

    private function listenOn(LoopInterface $loop, int $timeout, bool $rising, bool $falling): ?DigitalEdgeEvent
    {
        $edge = null;
        $expired = false;
        $timer = $timeout > 0 ? $loop->at($timeout / 1000, function () use (&$expired) { $expired = true; }) : null;

        $this->listeners++;
        $this->settle();

        try {
            $loop->until(function () use (&$edge, &$expired, $rising, $falling): bool {
                if ($this->closed) {
                    return true;
                }

                $edge ??= $this->take($rising, $falling);

                return ! is_null($edge) || $expired;
            });
        } finally {
            $timer?->cancel();
            $this->listeners--;
            $this->settle();
        }

        if (is_null($edge) && $this->closed) {
            throw DigitalIOException::pinClosed($this->pin, $this->device);
        }

        return $edge;
    }

    private function take(bool $rising, bool $falling): ?DigitalEdgeEvent
    {
        foreach ($this->unread as $i => $edge) {
            if ($this->passes($edge, $rising, $falling)) {
                array_splice($this->unread, $i, 1);

                return $edge;
            }
        }

        return null;
    }

    private function passes(DigitalEdgeEvent $edge, bool $rising, bool $falling): bool
    {
        return $edge->edge === SignalEdge::RISING ? $rising : $falling;
    }

    private function eventLoop(): ?LoopInterface
    {
        return is_null($this->loop_resolver) ? null : ($this->loop_resolver)();
    }
}
