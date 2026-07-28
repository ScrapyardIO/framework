<?php

namespace Fabricate\Queue\Events;

class JobDebounced
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  \Fabricate\Contracts\Queue\Job  $job
     * @param  mixed  $command
     */
    public function __construct(
        public $connectionName,
        public $job,
        public $command,
    ) {
    }
}
