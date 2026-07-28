<?php

namespace DeptOfScrapyardRobotics\Tests\Queue\Fixtures;

use Fabricate\Contracts\Queue\ShouldQueue;
use Fabricate\Core\Queue\Queueable;

class FakeSqsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }
}
