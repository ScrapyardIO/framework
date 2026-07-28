<?php

namespace Fabricate\Queue\Events;

class WorkerStarting
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \Fabricate\Queue\WorkerOptions  $workerOptions
     */
    public function __construct(
        public $connectionName,
        public $queue,
        public $workerOptions,
    ) {
    }
}
