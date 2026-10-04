<?php

namespace ScrapyardIO\Tests\Fixtures;

use Throwable;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Contracts\IOPools\WorkerPools\ShouldPool;
use Voyager\Contracts\IOPools\WorkerPools\WorkerPool;

/** A worker pool the test drives by hand: gigs wait here until finish() or fail(). */
final class ManualPool implements WorkerPool
{
    /** @var list<array{ShouldPool, Promise}> */
    public array $running = [];

    public function __construct(
        private readonly Loop $loop,
    ) {}

    public function submit(ShouldPool $gig): Promise
    {
        $promise = $this->loop->promise();
        $this->running[] = [$gig, $promise];

        return $promise;
    }

    /** Runs the oldest gig here, as a worker would, and settles its promise. */
    public function finish(): void
    {
        [$gig, $promise] = array_shift($this->running);

        try {
            $promise->resolve($gig->handle());
        } catch (Throwable $e) {
            $promise->reject($e);
        }
    }

    /** Settles the oldest gig with $reason without running it. */
    public function fail(Throwable $reason): void
    {
        [, $promise] = array_shift($this->running);
        $promise->reject($reason);
    }

    public function warm(int $count): void {}

    public function workerCount(): int
    {
        return count($this->running);
    }

    public function shutDown(): void {}
}
