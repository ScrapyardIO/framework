<?php

namespace Fabricate\Redis\Limiters;

use Fabricate\Contracts\Redis\LimiterTimeoutException;
use Fabricate\NutsAndBolts\Concerns\InteractsWithTime;
use Fabricate\Redis\Connections\Connection;
use Throwable;

class ConcurrencyLimiterBuilder
{
    use InteractsWithTime;

    /**
     * The Redis connection.
     *
     * @var Connection
     */
    public Connection $connection;

    /**
     * The name of the lock.
     *
     * @var string
     */
    public string $name;

    /**
     * The maximum number of entities that can hold the lock at the same time.
     *
     * @var int
     */
    public int $maxLocks;

    /**
     * The number of seconds to maintain the lock until it is automatically released.
     *
     * @var int
     */
    public int $releaseAfter = 60;

    /**
     * The amount of time to block until a lock is available.
     *
     * @var int
     */
    public int $timeout = 3;

    /**
     * The number of milliseconds to wait between attempts to acquire the lock.
     *
     * @var int
     */
    public int $sleep = 250;

    /**
     * Create a new builder instance.
     *
     * @param Connection $connection
     * @param string $name
     */
    public function __construct(Connection $connection, string $name)
    {
        $this->name = $name;
        $this->connection = $connection;
    }

    /**
     * Set the maximum number of locks that can be obtained per time window.
     *
     * @param int $maxLocks
     * @return $this
     */
    public function limit(int $maxLocks): static
    {
        $this->maxLocks = $maxLocks;

        return $this;
    }

    /**
     * Set the number of seconds until the lock will be released.
     *
     * @param int $releaseAfter
     * @return $this
     */
    public function releaseAfter(int $releaseAfter): static
    {
        $this->releaseAfter = $this->secondsUntil($releaseAfter);

        return $this;
    }

    /**
     * Set the amount of time to block until a lock is available.
     *
     * @param int $timeout
     * @return $this
     */
    public function block(int $timeout): static
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * The number of milliseconds to wait between lock acquisition attempts.
     *
     * @param int $sleep
     * @return $this
     */
    public function sleep(int $sleep): static
    {
        $this->sleep = $sleep;

        return $this;
    }

    /**
     * Execute the given callback if a lock is obtained, otherwise call the failure callback.
     *
     * @param  callable  $callback
     * @param  callable|null  $failure
     * @return mixed
     *
     * @throws LimiterTimeoutException|Throwable
     */
    public function then(callable $callback, ?callable $failure = null): mixed
    {
        try {
            return new ConcurrencyLimiter(
                $this->connection, $this->name, $this->maxLocks, $this->releaseAfter
            )->block($this->timeout, $callback, $this->sleep);
        } catch (LimiterTimeoutException $e) {
            if ($failure) {
                return $failure($e);
            }

            throw $e;
        }
    }
}
