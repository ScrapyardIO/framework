<?php

namespace Fabricate\Console\Events;

use Fabricate\Console\Scheduling\Event;

class ScheduledTaskStarting
{
    public function __construct(
        public Event $task,
    ) {
    }
}
