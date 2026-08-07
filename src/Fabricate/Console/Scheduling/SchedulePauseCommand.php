<?php

namespace Fabricate\Console\Scheduling;

use Fabricate\Console\Command;
use Fabricate\Console\Events\SchedulePaused;
use Fabricate\Contracts\Cache\Repository as Cache;
use Fabricate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:pause')]
class SchedulePauseCommand extends Command
{
    protected string $description = 'Pause the scheduler';

    public function handle(Cache $cache, Dispatcher $dispatcher): int
    {
        if (! Schedule::$pausable) {
            $this->components->error('Schedule pausing is currently disabled.');

            return self::FAILURE;
        }

        $cache->forever(ScheduleSignalKey::PAUSED->value, true);

        $dispatcher->dispatch(new SchedulePaused);

        $this->components->info('Scheduled task processing has been paused.');

        return self::SUCCESS;
    }
}
