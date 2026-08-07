<?php

namespace Fabricate\Console\Events;

use Fabricate\Console\Scheduling\Event;

class ScheduledTaskSkipped
{
    public function __construct(
        public Event $task,
    ) {
    }
}
