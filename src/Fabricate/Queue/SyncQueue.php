<?php

namespace Fabricate\Queue;

use Fabricate\Contracts\Core\Machine;
use Fabricate\Contracts\Queue\Queue;

class SyncQueue implements Queue
{
    public function __construct(
        protected Machine $machine
    ) {
    }

    public function push(mixed $job, mixed $data = '', ?string $queue = null): mixed
    {
        return $this->executeJob($job, $data);
    }

    public function later(mixed $delay, mixed $job, mixed $data = '', ?string $queue = null): mixed
    {
        return $this->executeJob($job, $data);
    }

    public function bulk(array $jobs, mixed $data = '', ?string $queue = null): void
    {
        foreach ($jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    protected function executeJob(mixed $job, mixed $data = ''): mixed
    {
        if (is_callable($job)) {
            return $this->machine->call($job, is_array($data) ? $data : []);
        }

        if (is_object($job)) {
            if (method_exists($job, 'handle')) {
                return $this->machine->call([$job, 'handle'], is_array($data) ? $data : []);
            }

            if (method_exists($job, '__invoke')) {
                return $this->machine->call($job, is_array($data) ? $data : []);
            }
        }

        return null;
    }
}
