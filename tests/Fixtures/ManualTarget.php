<?php

namespace ScrapyardIO\Tests\Fixtures;

use Throwable;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Contracts\IOPools\ShouldPool;
use Voyager\Contracts\IOPools\WorkTarget;

/** A work target the test drives by hand: gigs wait here until finish() or fail(). */
final class ManualTarget implements WorkTarget
{
    /** @var list<array{ShouldPool, Promise}> */
    public array $running = [];

    public function __construct(
        private readonly Loop $loop,
    ) {}

    public function run(ShouldPool $gig): Promise
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
}
