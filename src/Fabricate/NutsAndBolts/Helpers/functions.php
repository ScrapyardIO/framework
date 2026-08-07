<?php

namespace Fabricate\NutsAndBolts\Helpers;

use Symfony\Component\Process\PhpExecutableFinder;
use Throwable;

if (! function_exists('Fabricate\NutsAndBolts\Helpers\php_binary')) {
    /**
     * Determine the PHP Binary.
     */
    function php_binary(): string
    {
        return (new PhpExecutableFinder)->find(false) ?: 'php';
    }
}

if (! function_exists('Fabricate\NutsAndBolts\Helpers\workshop_binary')) {
    /**
     * Determine the proper Workshop executable.
     */
    function workshop_binary(): string
    {
        return defined('WORKSHOP_BINARY') ? WORKSHOP_BINARY : 'workshop';
    }
}

if (! function_exists('Fabricate\NutsAndBolts\Helpers\enum_value')) {
    function enum_value(mixed $value, mixed $default = null): mixed
    {
        return match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \UnitEnum => $value->name,
            default => $value ?? value($default),
        };
    }
}

if (! function_exists('Fabricate\NutsAndBolts\Helpers\retry')) {
    /**
     * Retry an operation a given number of times.
     *
     * @param  array<int, int>|int  $times
     * @param  callable(int): mixed  $callback
     * @param  (callable(int, Throwable): int)|int  $sleepMilliseconds
     * @param  callable(Throwable): bool|null  $when
     */
    function retry(array|int $times, callable $callback, callable|int $sleepMilliseconds = 0, ?callable $when = null): mixed
    {
        $attempts = 0;
        $backoff = [];

        if (is_array($times)) {
            $backoff = $times;
            $times = count($times) + 1;
        }

        beginning:
        $attempts++;
        $times--;

        try {
            return $callback($attempts);
        } catch (Throwable $exception) {
            if ($times < 1 || (! is_null($when) && ! $when($exception))) {
                throw $exception;
            }

            $delay = $backoff[$attempts - 1] ?? $sleepMilliseconds;
            $delay = value($delay, $attempts, $exception);

            if ($delay > 0) {
                usleep($delay * 1000);
            }

            goto beginning;
        }
    }
}
