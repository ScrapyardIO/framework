<?php

namespace Fabricate\Concurrency;

use Carbon\CarbonInterval;
use Closure;
use Fabricate\Contracts\Concurrency\Driver;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Defer\DeferredCallback;

use function Fabricate\Core\Helpers\defer;

class SyncDriver implements Driver
{
    /**
     * Run the given tasks concurrently and return an array containing the results.
     */
    public function run(Closure|array $tasks, CarbonInterval|int|null $timeout = null): array
    {
        return Collection::wrap($tasks)->map(
            fn ($task) => $task()
        )->all();
    }

    /**
     * Start the given tasks in the background after the current task has finished.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        return defer(fn () => Collection::wrap($tasks)->each(fn ($task) => $task()));
    }
}
