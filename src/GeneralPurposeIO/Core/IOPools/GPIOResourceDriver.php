<?php

namespace GeneralPurposeIO\Core\IOPools;

use Closure;
use GeneralPurposeIO\Contracts\Core\ByteSource;
use GeneralPurposeIO\Contracts\Core\EdgeSource;
use GeneralPurposeIO\Contracts\Core\GPIOResourceDriver as Contract;
use GeneralPurposeIO\Contracts\Core\Mail\DigitalEdgeOccurrence;
use GeneralPurposeIO\Contracts\Core\Mail\SourceFaultOccurrence;
use GeneralPurposeIO\Contracts\Core\Mail\TransferCompletion;
use GeneralPurposeIO\Contracts\Core\Mail\UARTBytesOccurrence;
use GeneralPurposeIO\Contracts\Core\Recurrence;
use GeneralPurposeIO\Contracts\NutsAndBolts\GPIOException;
use Throwable;
use Voyager\Contracts\IOPools\PoolPump;
use Voyager\Contracts\IOPools\QueuedIO;
use Voyager\IOPools\Presumption;

/**
 * The gpio dock resource. One tick: poll watched pins and received ports
 * with zero timeout, run deferred work FIFO, run the recurrences that are
 * due, send one chunk of every stream. Never waits: a closure that blocks
 * spends the IC's budget, the same way renderFrame() spends Surface's.
 *
 * Only what was registered before the tick started runs in that tick; a
 * hook that defers, recurs or streams lands on the next one. Faults from a
 * source or a recurrence become SourceFaultOccurrence mail and the thing
 * stays registered — dropping it is IC policy. Hooks run inside tick and
 * are not contained: an IC bug in a hook surfaces loudly.
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

    /** @var array<string, array{Recurrence, Closure, int}> name → recurrence, work, ticks elapsed */
    protected array $recurrences = [];

    /** @var array<string, array{Closure, string, int, int, Presumption}> name → write, bytes, chunk, sent, presumption */
    protected array $streams = [];

    public function __construct(
        protected PoolPump $pump,
        protected ?int $defer_per_tick = null,
    ) {
        if (! is_null($defer_per_tick) && $defer_per_tick < 1) {
            throw GPIOException::invalidDeferBudget($defer_per_tick);
        }
    }

    // --- watch / receive -------------------------------------------------

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

    // --- defer -----------------------------------------------------------

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

    // --- every -----------------------------------------------------------

    public function every(string $name, Closure $work, int $ticks = 1): Recurrence
    {
        if ($ticks < 1) {
            throw GPIOException::invalidCadence($ticks);
        }

        if (isset($this->recurrences[$name])) {
            throw GPIOException::recurrenceInFlight($name);
        }

        $recurrence = new Recurrence($name, $ticks);
        $this->recurrences[$name] = [$recurrence, $work, 0];

        return $recurrence;
    }

    public function recurring(string $name): ?Recurrence
    {
        return $this->recurrences[$name][0] ?? null;
    }

    // --- stream ----------------------------------------------------------

    public function stream(string $name, Closure $write, string $bytes, int $chunk): Presumption
    {
        if ($chunk < 1) {
            throw GPIOException::invalidChunk($chunk);
        }

        if (isset($this->streams[$name])) {
            throw GPIOException::streamInFlight($name);
        }

        $presumption = new Presumption($name);
        $this->streams[$name] = [$write, $bytes, $chunk, 0, $presumption];

        return $presumption;
    }

    public function streaming(string $name): ?Presumption
    {
        return $this->streams[$name][4] ?? null;
    }

    // --- tick ------------------------------------------------------------

    public function tick(): void
    {
        $deferred = array_splice($this->deferred, 0, is_null($this->defer_per_tick) ? count($this->deferred) : min($this->defer_per_tick, count($this->deferred)));
        $recurrences = array_keys($this->recurrences);
        $streams = array_keys($this->streams);

        $this->pollSources();
        $this->runDeferred($deferred);
        $this->runRecurrences($recurrences);
        $this->sendChunks($streams);
    }

    protected function pollSources(): void
    {
        foreach ($this->watched as [$source, $rising, $falling]) {
            $label = is_null($source->device()) ? "edge.{$source->offset()}" : "edge.{$source->device()}.{$source->offset()}";

            try {
                $events = $source->pollEdges($rising, $falling);
            } catch (Throwable $error) {
                $this->pump->push(new SourceFaultOccurrence($label, $error));

                continue;
            }

            foreach ($events as $event) {
                $this->pump->push(new DigitalEdgeOccurrence($source->device(), $source->offset(), $event->edge, (int) $event->timestamp));
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
    }

    /** @param list<array{string, Closure, ?Closure, Presumption}> $deferred */
    protected function runDeferred(array $deferred): void
    {
        foreach ($deferred as [$name, $work, $envelope, $presumption]) {
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

    /** @param list<string> $names the recurrences registered before this tick started */
    protected function runRecurrences(array $names): void
    {
        foreach ($names as $name) {
            if (! isset($this->recurrences[$name])) {
                continue;
            }

            [$recurrence, $work, $elapsed] = $this->recurrences[$name];

            if ($recurrence->stopped()) {
                unset($this->recurrences[$name]);

                continue;
            }

            $elapsed++;
            $this->recurrences[$name][2] = $elapsed;

            if ($elapsed % $recurrence->ticks !== 0) {
                continue;
            }

            try {
                $completion = new TransferCompletion($name, $work());
            } catch (Throwable $error) {
                $this->pump->push(new SourceFaultOccurrence("every.{$name}", $error));
                $recurrence->failed($error);
            }

            if (isset($completion)) {
                $this->pump->push($completion);
                $recurrence->ran($completion);
                unset($completion);
            }

            if ($recurrence->stopped()) {
                unset($this->recurrences[$name]);
            }
        }
    }

    /** @param list<string> $names the streams registered before this tick started */
    protected function sendChunks(array $names): void
    {
        foreach ($names as $name) {
            if (! isset($this->streams[$name])) {
                continue;
            }

            [$write, $bytes, $chunk, $sent, $presumption] = $this->streams[$name];
            $total = strlen($bytes);

            if ($sent < $total) {
                $piece = substr($bytes, $sent, $chunk);

                try {
                    $write($piece);
                } catch (Throwable $error) {
                    unset($this->streams[$name]);
                    $this->finishStream($presumption, new TransferCompletion($name, $sent, $error));

                    continue;
                }

                $sent += strlen($piece);
                $this->streams[$name][3] = $sent;
                $presumption->notifyProgress($sent, $total);
            }

            if ($sent >= $total) {
                unset($this->streams[$name]);
                $this->finishStream($presumption, new TransferCompletion($name, $sent));
            }
        }
    }

    protected function finishStream(Presumption $presumption, TransferCompletion $completion): void
    {
        $this->pump->push($completion);
        $presumption->settle($completion);
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
