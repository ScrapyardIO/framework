<?php

namespace Fabricate\Sketches\Flow;

use Fabricate\Contracts\Concurrency\Driver;
use RuntimeException;
use Throwable;

/**
 * Async graph node. Fan-out/work execution goes through Concurrency drivers.
 *
 * Default sketches concurrency driver is `fiber` (cooperative, same-process).
 * Use `pokio` when nunomaduro/pokio is installed and fork fan-out is desired.
 */
class AsyncNode extends Node
{
    public function __construct(
        public int $maxRetries = 1,
        public int $waitSeconds = 0,
        protected ?string $concurrencyDriver = null,
    ) {
        parent::__construct($maxRetries, $waitSeconds);
    }

    public function prepAsync(mixed &$shared): mixed
    {
        return $this->prep($shared);
    }

    public function execAsync(mixed $prepRes): mixed
    {
        return $this->exec($prepRes);
    }

    public function postAsync(mixed &$shared, mixed $prepRes, mixed $execRes): mixed
    {
        return $this->post($shared, $prepRes, $execRes);
    }

    public function execFallbackAsync(mixed $prepRes, Throwable $e): mixed
    {
        return $this->execFallback($prepRes, $e);
    }

    /**
     * Run this node asynchronously via Concurrency.
     */
    public function runAsync(mixed &$shared): mixed
    {
        $prepRes = $this->prepAsync($shared);

        try {
            $execRes = $this->runExecAsync($prepRes);
        } catch (Throwable $e) {
            $execRes = $this->execFallbackAsync($prepRes, $e);
        }

        return $this->postAsync($shared, $prepRes, $execRes);
    }

    protected function runExecAsync(mixed $prepRes): mixed
    {
        $driver = $this->resolveDriver();

        for ($this->currentRetry = 0; $this->currentRetry < $this->maxRetries; $this->currentRetry++) {
            try {
                $results = $driver->run([
                    fn () => $this->execAsync($prepRes),
                ]);

                return $results[0];
            } catch (Throwable $e) {
                if ($this->currentRetry === $this->maxRetries - 1) {
                    throw $e;
                }

                if ($this->waitSeconds > 0) {
                    sleep($this->waitSeconds);
                }
            }
        }

        return null;
    }

    public function run(mixed &$shared): mixed
    {
        return $this->runAsync($shared);
    }

    protected function resolveDriver(): Driver
    {
        if (! function_exists('app')) {
            throw new RuntimeException('AsyncNode requires a bound application container.');
        }

        $name = $this->concurrencyDriver
            ?? config('sketches.concurrency', 'fiber');

        return app('concurrency')->driver($name);
    }
}
