<?php

namespace Fabricate\Queue;

use Fabricate\Contracts\Core\Machine;
use Fabricate\Contracts\Queue\Factory;
use Fabricate\Contracts\Queue\Monitor;
use Fabricate\Contracts\Queue\Queue;
use InvalidArgumentException;
use Throwable;

class QueueManager implements Factory, Monitor
{
    /**
     * @var array<string, Queue>
     */
    protected array $connections = [];

    public function __construct(
        protected Machine $machine
    ) {
    }

    public function connection(?string $name = null): Queue
    {
        $name = $name ?: $this->getDefaultDriver();

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);
        }

        return $this->connections[$name];
    }

    public function size(?string $queue = null): int
    {
        // Sync queue executes immediately and does not store queued jobs.
        return 0;
    }

    protected function resolve(string $name): Queue
    {
        $config = $this->getConfig($name);
        $driver = (string) ($config['driver'] ?? '');

        return match ($driver) {
            'sync', 'deferred' => $this->createSyncDriver(),
            'null' => $this->createNullDriver(),
            'failover' => $this->createFailoverDriver($config),
            default => throw new InvalidArgumentException(
                "Queue driver [{$driver}] for connection [{$name}] is not implemented. ".
                'Implemented drivers: sync, deferred, null, failover.'
            ),
        };
    }

    protected function getDefaultDriver(): string
    {
        if ($this->machine->bound('config')) {
            $configured = (string) ($this->machine['config']['queue.default'] ?? 'sync');

            $connection = $this->getConfig($configured);

            if (! empty($connection['driver'])) {
                return $configured;
            }
        }

        return 'sync';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConfig(string $name): array
    {
        if (! $this->machine->bound('config')) {
            return $name === 'sync'
                ? ['driver' => 'sync']
                : [];
        }

        return $this->machine['config']["queue.connections.{$name}"] ?: [];
    }

    protected function createSyncDriver(): Queue
    {
        return new SyncQueue($this->machine);
    }

    protected function createNullDriver(): Queue
    {
        return new NullQueue;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function createFailoverDriver(array $config): Queue
    {
        $connections = [];

        foreach ((array) ($config['connections'] ?? []) as $connectionName) {
            try {
                $connections[] = $this->connection((string) $connectionName);
            } catch (Throwable) {
                // Intentionally skip unavailable failover targets.
            }
        }

        if ($connections === []) {
            $connections[] = $this->createSyncDriver();
        }

        return new FailoverQueue($connections);
    }
}
