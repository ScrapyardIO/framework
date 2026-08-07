<?php

namespace Fabricate\Concurrency;

use Carbon\CarbonInterval;
use Closure;
use Fabricate\Contracts\Concurrency\Driver;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Defer\DeferredCallback;
use RuntimeException;

use function Fabricate\Core\Helpers\defer;

/**
 * Concurrency driver backed by nunomaduro/pokio (async/await over pcntl fork).
 *
 * Package is suggested, not required. Creating this driver without pokio installed throws.
 */
class PokioDriver implements Driver
{
    /**
     * Create a new Pokio-backed concurrency driver.
     *
     * @throws RuntimeException
     */
    public function __construct()
    {
        if (! function_exists('async') || ! function_exists('await')) {
            throw new RuntimeException(
                'The "pokio" concurrency driver requires the "nunomaduro/pokio" Composer package.'
            );
        }
    }

    /**
     * Run the given tasks concurrently via Pokio and return results.
     */
    public function run(Closure|array $tasks, CarbonInterval|int|null $timeout = null): array
    {
        $tasks = Arr::wrap($tasks);
        $keys = array_keys($tasks);
        $promises = [];

        foreach ($tasks as $key => $task) {
            $promises[$key] = async($task);
        }

        $resolved = await(array_values($promises));

        return array_combine($keys, $resolved);
    }

    /**
     * Defer Pokio fan-out until after the current task finishes.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        return defer(fn () => $this->run($tasks));
    }
}
