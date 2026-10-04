<?php

namespace ScrapyardIO\Tests\Fixtures;

use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Contracts\IOPools\WorkerPools\ShouldPool;
use Voyager\Contracts\IOPools\WorkerPools\WorkerPool;

/** A worker pool that runs each gig in this process on a later loop turn, through Loop::defer(). */
final class DeferredPool implements WorkerPool
{
    public function __construct(
        private readonly Loop $loop,
    ) {}

    public function submit(ShouldPool $gig): Promise
    {
        return $this->loop->defer(fn (): mixed => $gig->handle());
    }

    public function warm(int $count): void {}

    public function workerCount(): int
    {
        return 0;
    }

    public function shutDown(): void {}
}
