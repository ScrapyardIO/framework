<?php

namespace Fabricate\Console\Events;

use Fabricate\Console\Scheduling\Event;
use Throwable;

class ScheduledTaskFailed
{
    public function __construct(
        public Event $task,
        public Throwable $exception,
    ) {
    }
}
