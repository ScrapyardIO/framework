<?php

namespace Fabricate\Concurrency\Fiber;

use Fiber;
use RuntimeException;

if (! function_exists(__NAMESPACE__.'\\suspend')) {
    /**
     * Suspend the current fiber (no-op outside a fiber).
     */
    function suspend(mixed $value = null): mixed
    {
        if (is_null(Fiber::getCurrent())) {
            return $value;
        }

        return Fiber::suspend($value);
    }
}

if (! function_exists(__NAMESPACE__.'\\delay')) {
    /**
     * Cooperative delay: suspends once then sleeps (still blocks the process during sleep).
     *
     * Prefer short sleeps; for true parallel waits use the pokio/process drivers.
     */
    function delay(int|float $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }

        suspend();

        usleep((int) ($seconds * 1_000_000));
    }
}

if (! function_exists(__NAMESPACE__.'\\await')) {
    /**
     * Await a list of closures cooperatively via FiberDriver semantics.
     *
     * @param  array<int|string, callable(): mixed>  $tasks
     * @return array<int|string, mixed>
     */
    function await(array $tasks): array
    {
        $driver = new \Fabricate\Concurrency\FiberDriver;

        return $driver->run($tasks);
    }
}
