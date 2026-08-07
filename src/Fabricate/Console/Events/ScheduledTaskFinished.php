<?php

namespace Fabricate\Console\Events;

use Fabricate\Console\Scheduling\Event;

class ScheduledTaskFinished
{
    public function __construct(
        public Event $task,
        public float $runtime,
    ) {
    }
}
