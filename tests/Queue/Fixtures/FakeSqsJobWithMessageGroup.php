<?php

namespace DeptOfScrapyardRobotics\Tests\Queue\Fixtures;

use Fabricate\Contracts\Queue\ShouldQueue;
use Fabricate\Core\Queue\Queueable;

class FakeSqsJobWithMessageGroup implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }

    /**
     * Message group method called by SqsQueue.
     *
     * @return string
     */
    public function messageGroup(): string
    {
        return 'group-1';
    }
}
