<?php

namespace Fabricate\Queue\Events;

use Fabricate\Queue\WorkerOptions;

class WorkerIdle
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \Fabricate\Queue\WorkerOptions  $workerOptions
     */
    public function __construct(
        public string $connectionName,
        public string $queue,
        public WorkerOptions $workerOptions,
    ) {
    }
}
