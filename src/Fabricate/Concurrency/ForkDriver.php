<?php

namespace Fabricate\Concurrency;

use Carbon\CarbonInterval;
use Closure;
use Fabricate\Contracts\Concurrency\Driver;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Defer\DeferredCallback;
use Spatie\Fork\Fork;

use function Fabricate\Core\Helpers\defer;

class ForkDriver implements Driver
{
    /**
     * Run the given tasks concurrently and return an array containing the results.
     */
    public function run(Closure|array $tasks, CarbonInterval|int|null $timeout = null): array
    {
        $tasks = Arr::wrap($tasks);

        $keys = array_keys($tasks);
        $values = array_values($tasks);

        /** @phpstan-ignore class.notFound (spatie/fork is not installed as it is practically incompatible with Windows) */
        $results = Fork::new()->run(...$values);

        ksort($results);

        return array_combine($keys, $results);
    }

    /**
     * Start the given tasks in the background after the current task has finished.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        return defer(fn () => $this->run($tasks));
    }
}
