<?php

namespace Fabricate\Queue\Events;

class JobReleasedAfterException
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  \Fabricate\Contracts\Queue\Job  $job  The job instance.
     * @param  int|null  $backoff  The backoff delay.
     */
    public function __construct(
        public $connectionName,
        public $job,
        public $backoff = null
    ) {
    }
}
