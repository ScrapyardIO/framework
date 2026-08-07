<?php

namespace Fabricate\Console\Scheduling;

use Fabricate\Console\Command;
use Fabricate\Console\Events\ScheduleResumed;
use Fabricate\Contracts\Cache\Repository as Cache;
use Fabricate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:resume', aliases: ['schedule:continue'])]
class ScheduleResumeCommand extends Command
{
    protected string $description = 'Resume the schedule';

    /** @var list<string> */
    protected array $aliases = ['schedule:continue'];

    public function handle(Cache $cache, Dispatcher $dispatcher): int
    {
        $cache->forget(ScheduleSignalKey::PAUSED->value);

        $dispatcher->dispatch(new ScheduleResumed);

        $this->components->info('Scheduled task processing has resumed.');

        return self::SUCCESS;
    }
}
