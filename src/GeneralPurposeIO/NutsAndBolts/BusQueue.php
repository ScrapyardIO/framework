<?php

namespace GeneralPurposeIO\NutsAndBolts;

use Closure;
use Fiber;
use Throwable;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\Promise;

/**
 * Offloaded bus jobs that must not overlap: one runs at a time, first in first out.
 * Each entry carries its owner (a slave address, a chip select), so closing one slave touches only its own entries.
 * pause() keeps queued jobs from starting while something outside the queue has the bus (an SPI select()).
 */
final class BusQueue
{
    /** @var list<array{int, Closure(): Promise, Promise}> */
    private array $pending = [];

    private bool $running = false;

    private ?int $running_owner = null;

    private ?Fiber $running_fiber = null;

    /** The caller-facing promise of the running job. */
    private ?Promise $running_promise = null;

    /** How many holders have paused the queue. Queued jobs start only at zero. */
    private int $paused = 0;

    public function __construct(
        private readonly Loop $loop,
    ) {}

    /** @param Closure(): Promise $start begins the job and hands back its promise */
    public function push(int $owner, Closure $start): Promise
    {
        $promise = $this->loop->promise();
        $this->pending[] = [$owner, $start, $promise];
        $this->next();

        return $promise;
    }

    /** Marks the fiber the running job executes in: that job's own blocking calls must not wait for it. */
    public function claim(Fiber $fiber): void
    {
        $this->running_fiber = $fiber;
    }

    public function idle(): bool
    {
        return ! $this->running && $this->pending === [];
    }

    /**
     * Waits (borrowing the loop, or suspending a fiber) until every job queued before this call has settled.
     * Jobs queued while it waits are not its business, so a producer that keeps re-queueing cannot hold it off.
     */
    public function drain(): void
    {
        if ($this->insideRunningJob()) {
            return;
        }

        $last = $this->pending === [] ? $this->running_promise : $this->pending[array_key_last($this->pending)][2];

        if (! is_null($last)) {
            $this->loop->until(fn (): bool => $last->settled());
        }
    }

    /** Rejects $owner's jobs that have not started, then waits for its running one to settle. */
    public function abandon(int $owner, Throwable $reason): void
    {
        $kept = [];

        foreach ($this->pending as $entry) {
            if ($entry[0] === $owner) {
                $entry[2]->reject($reason);
            } else {
                $kept[] = $entry;
            }
        }

        $this->pending = $kept;

        if ($this->runningFor($owner) && ! $this->insideRunningJob()) {
            $this->loop->until(fn (): bool => ! $this->runningFor($owner));
        }
    }

    /** Queued jobs wait until every pause() has had its resume(). A running job carries on. */
    public function pause(): void
    {
        $this->paused++;
    }

    public function resume(): void
    {
        $this->paused = max(0, $this->paused - 1);
        $this->next();
    }

    /** Waits (borrowing the loop, or suspending a fiber) until no job is running. From inside the running job, returns at once. */
    public function awaitRunning(): void
    {
        if ($this->running && ! $this->insideRunningJob()) {
            $this->loop->until(fn (): bool => ! $this->running);
        }
    }

    /** Whether the caller is the running job's own fiber. */
    public function insideRunningJob(): bool
    {
        $fiber = Fiber::getCurrent();

        return ! is_null($fiber) && $fiber === $this->running_fiber;
    }

    private function next(): void
    {
        if ($this->running || $this->paused > 0 || $this->pending === []) {
            return;
        }

        [$owner, $start, $promise] = array_shift($this->pending);
        [$this->running, $this->running_owner, $this->running_promise] = [true, $owner, $promise];

        try {
            $job = $start();
        } catch (Throwable $e) {
            $this->settle($promise, null, $e);

            return;
        }

        $job->then(fn (mixed $value) => $this->settle($promise, $value, null))
            ->error(fn (Throwable $e) => $this->settle($promise, null, $e));
    }

    private function settle(Promise $promise, mixed $value, ?Throwable $reason): void
    {
        [$this->running, $this->running_owner, $this->running_fiber, $this->running_promise] = [false, null, null, null];

        is_null($reason) ? $promise->resolve($value) : $promise->reject($reason);

        $this->next();
    }

    private function runningFor(int $owner): bool
    {
        return $this->running && $this->running_owner === $owner;
    }
}
