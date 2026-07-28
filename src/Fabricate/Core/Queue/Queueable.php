<?php

namespace Fabricate\Core\Queue;

use Fabricate\Bus\Queueable as QueueableByBus;
use Fabricate\Core\Bus\Dispatchable;
use Fabricate\Queue\InteractsWithQueue;
use Fabricate\Queue\SerializesModels;

trait Queueable
{
    use Dispatchable, InteractsWithQueue, QueueableByBus, SerializesModels;
}
