<?php

namespace GeneralPurposeIO\Core\IOPools;

use Closure;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\Core\ByteSource;
use GeneralPurposeIO\Contracts\Core\EdgeSource;
use GeneralPurposeIO\Contracts\Core\GPIOResourceDriver as Contract;
use GeneralPurposeIO\Contracts\Core\Mail\DigitalEdgeOccurrence;
use GeneralPurposeIO\Contracts\Core\Mail\SourceFaultOccurrence;
use GeneralPurposeIO\Contracts\Core\Mail\TransferCompletion;
use GeneralPurposeIO\Contracts\Core\Mail\UARTBytesOccurrence;
use Throwable;
use Voyager\Contracts\IOPools\PoolPump;
use Voyager\Contracts\IOPools\QueuedIO;
use Voyager\IOPools\Presumption;

/**
 * The gpio dock resource. Polls watched pins and received ports with zero
 * timeout, then runs deferred work FIFO. Never waits: a deferred closure that
 * blocks spends the IC's budget, the same way renderFrame() spends Surface's.
 * Each tick runs only the work queued before it started; a hook that
 * re-defers lands on the next tick. A faulting source becomes a
 * SourceFaultOccurrence and the source stays registered — dropping it is
 * IC policy. Hooks run inside tick and are not contained: an IC bug in a
 * hook surfaces loudly.
 */
final class GPIOResourceDriver implements Contract
{
    /** @var array<int, array{EdgeSource, bool, bool}> */
    protected array $watched = [];

    /** @var array<int, array{ByteSource, int}> */
    protected array $received = [];

    /** @var list<array{string, Closure, ?Closure, Presumption}> */
    protected array $deferred = [];

    /** @var array<string, Presumption> */
    protected array $in_flight = [];

    public function __construct(
        protected PoolPump $pump,
        protected ?int $defer_per_tick = null,
    ) {
        if (! is_null($defer_per_tick) && $defer_per_tick < 1) {
            throw GPIOException::invalidDeferBudget($defer_per_tick);
        }
    }

    public function watch(EdgeSource $source, bool $rising = true, bool $falling = false): static
    {
        $this->watched[spl_object_id($source)] = [$source, $rising, $falling];

        return $this;
    }

    public function unwatch(EdgeSource $source): static
    {
        unset($this->watched[spl_object_id($source)]);

        return $this;
    }

    public function receive(ByteSource $source, int $max_bytes = 4096): static
    {
        $this->received[spl_object_id($source)] = [$source, $max_bytes];

        return $this;
    }

    public function stopReceiving(ByteSource $source): static
    {
        unset($this->received[spl_object_id($source)]);

        return $this;
    }

    public function defer(string $name, Closure $work, ?Closure $envelope = null): Presumption
    {
        if (isset($this->in_flight[$name])) {
            throw GPIOException::transferInFlight($name);
        }

        $presumption = new Presumption($name);
        $this->in_flight[$name] = $presumption;
        $this->deferred[] = [$name, $work, $envelope, $presumption];

        return $presumption;
    }

    public function inFlight(string $name): ?Presumption
    {
        return $this->in_flight[$name] ?? null;
    }

    public function tick(): void
    {
        foreach ($this->watched as [$source, $rising, $falling]) {
            try {
                $events = $source->pollEdges($rising, $falling);
            } catch (Throwable $error) {
                $this->pump->push(new SourceFaultOccurrence("edge.{$source->offset()}", $error));

                continue;
            }

            foreach ($events as $event) {
                $this->pump->push(new DigitalEdgeOccurrence($source->offset(), $event->edge, (int) $event->timestamp));
            }
        }

        foreach ($this->received as [$source, $max_bytes]) {
            try {
                $bytes = $source->pollBytes($max_bytes);
            } catch (Throwable $error) {
                $this->pump->push(new SourceFaultOccurrence("uart.{$source->path()}", $error));

                continue;
            }

            if ($bytes !== '') {
                $this->pump->push(new UARTBytesOccurrence($source->path(), $bytes));
            }
        }

        $take = is_null($this->defer_per_tick) ? count($this->deferred) : min($this->defer_per_tick, count($this->deferred));

        foreach (array_splice($this->deferred, 0, $take) as [$name, $work, $envelope, $presumption]) {
            try {
                $mail = new TransferCompletion($name, $work());
            } catch (Throwable $error) {
                $mail = new TransferCompletion($name, null, $error);
            }

            unset($this->in_flight[$name]);
            $this->pump->push($this->outgoing($mail, $envelope));
            $presumption->settle($mail);
        }
    }

    /** The envelope's mail, or the raw completion when there is no envelope, it throws, or it returns something that is not mail. */
    protected function outgoing(TransferCompletion $mail, ?Closure $envelope): QueuedIO
    {
        if (is_null($envelope)) {
            return $mail;
        }

        try {
            $out = $envelope($mail);
        } catch (Throwable) {
            return $mail;
        }

        return $out instanceof QueuedIO ? $out : $mail;
    }
}
