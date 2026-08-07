<?php

namespace Fabricate\Concurrency;

use Carbon\CarbonInterval;
use Closure;
use Fiber;
use Fabricate\Contracts\Concurrency\Driver;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Defer\DeferredCallback;
use Throwable;

use function Fabricate\Core\Helpers\defer;

/**
 * Cooperative same-process concurrency via PHP Fibers.
 *
 * Tasks interleave only when they suspend (Fiber::suspend / Fiber helpers).
 * Blocking calls without yield block the whole process.
 */
class FiberDriver implements Driver
{
    /**
     * Run the given tasks cooperatively and return an array containing the results.
     *
     * @throws Throwable
     */
    public function run(Closure|array $tasks, CarbonInterval|int|null $timeout = null): array
    {
        $tasks = Arr::wrap($tasks);
        $keys = array_keys($tasks);
        $fibers = [];
        $results = [];

        foreach ($tasks as $key => $task) {
            $fibers[$key] = new Fiber(static function () use ($task) {
                return $task();
            });
        }

        foreach ($fibers as $key => $fiber) {
            try {
                $fiber->start();
            } catch (Throwable $e) {
                throw $e;
            }

            if ($fiber->isTerminated()) {
                $results[$key] = $fiber->getReturn();
                unset($fibers[$key]);
            }
        }

        while ($fibers !== []) {
            foreach ($fibers as $key => $fiber) {
                if ($fiber->isSuspended()) {
                    try {
                        $fiber->resume();
                    } catch (Throwable $e) {
                        throw $e;
                    }
                }

                if ($fiber->isTerminated()) {
                    $results[$key] = $fiber->getReturn();
                    unset($fibers[$key]);
                }
            }
        }

        $ordered = [];

        foreach ($keys as $key) {
            $ordered[$key] = $results[$key];
        }

        return $ordered;
    }

    /**
     * Start the given tasks after the current task has finished (still cooperative).
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        return defer(fn () => $this->run($tasks));
    }
}
