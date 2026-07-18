<?php

namespace Fabricate\Queue;

use Fabricate\Contracts\Queue\Queue;
use RuntimeException;
use Throwable;

class FailoverQueue implements Queue
{
    /**
     * @param  list<Queue>  $connections
     */
    public function __construct(
        protected array $connections
    ) {
    }

    public function push(mixed $job, mixed $data = '', ?string $queue = null): mixed
    {
        $lastException = null;

        foreach ($this->connections as $connection) {
            try {
                return $connection->push($job, $data, $queue);
            } catch (Throwable $e) {
                $lastException = $e;
            }
        }

        if (! is_null($lastException)) {
            throw $lastException;
        }

        throw new RuntimeException('Failover queue has no available connections.');
    }

    public function later(mixed $delay, mixed $job, mixed $data = '', ?string $queue = null): mixed
    {
        $lastException = null;

        foreach ($this->connections as $connection) {
            try {
                return $connection->later($delay, $job, $data, $queue);
            } catch (Throwable $e) {
                $lastException = $e;
            }
        }

        if (! is_null($lastException)) {
            throw $lastException;
        }

        throw new RuntimeException('Failover queue has no available connections.');
    }

    public function bulk(array $jobs, mixed $data = '', ?string $queue = null): void
    {
        $lastException = null;

        foreach ($this->connections as $connection) {
            try {
                $connection->bulk($jobs, $data, $queue);
                return;
            } catch (Throwable $e) {
                $lastException = $e;
            }
        }

        if (! is_null($lastException)) {
            throw $lastException;
        }
    }
}
