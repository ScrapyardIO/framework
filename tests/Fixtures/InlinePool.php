<?php

namespace ScrapyardIO\Tests\Fixtures;

use Throwable;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Contracts\IOPools\WorkerPools\ShouldPool;
use Voyager\Contracts\IOPools\WorkerPools\WorkerPool;

/** A worker pool that runs each gig in this process the moment it is submitted, and settles its promise. */
final class InlinePool implements WorkerPool
{
    public int $submitted = 0;

    public function __construct(
        private readonly Loop $loop,
    ) {}

    public function submit(ShouldPool $gig): Promise
    {
        $this->submitted++;
        $promise = $this->loop->promise();

        try {
            $promise->resolve($gig->handle());
        } catch (Throwable $e) {
            $promise->reject($e);
        }

        return $promise;
    }

    public function warm(int $count): void {}

    public function workerCount(): int
    {
        return 0;
    }

    public function shutDown(): void {}
}
