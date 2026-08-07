<?php

namespace Fabricate\Console\Scheduling;

use Fabricate\Console\Command;
use Fabricate\Contracts\Cache\Repository as Cache;
use Fabricate\NutsAndBolts\Carbon;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:interrupt')]
class ScheduleInterruptCommand extends Command
{
    protected ?string $signature = 'schedule:interrupt';

    protected string $description = 'Interrupt the current schedule run';

    public function __construct(
        protected Cache $cache,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->cache->put(
            ScheduleSignalKey::INTERRUPT->value,
            true,
            Carbon::now()->endOfMinute()
        );

        $this->components->info('Broadcasting schedule interrupt signal.');
    }
}
