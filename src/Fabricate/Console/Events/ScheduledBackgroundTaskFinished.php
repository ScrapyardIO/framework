<?php

namespace Fabricate\Console\Events;

use Fabricate\Console\Scheduling\Event;

class ScheduledBackgroundTaskFinished
{
    public function __construct(
        public Event $task,
    ) {
    }
}
