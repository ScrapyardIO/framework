<?php

namespace Fabricate\Console\Scheduling;

use Fabricate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:clear-cache')]
class ScheduleClearCacheCommand extends Command
{
    protected ?string $signature = 'schedule:clear-cache';

    protected string $description = 'Delete the cached mutex files created by scheduler';

    public function handle(Schedule $schedule): void
    {
        $mutexCleared = false;

        foreach ($schedule->events() as $event) {
            if ($event->mutex->exists($event)) {
                $this->components->info(sprintf('Deleting mutex for [%s]', $event->command));

                $event->mutex->forget($event);

                $mutexCleared = true;
            }
        }

        if (! $mutexCleared) {
            $this->components->info('No mutex files were found.');
        }
    }
}
