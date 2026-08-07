<?php

namespace Fabricate\Console\Scheduling;

use Fabricate\Console\Command;
use Fabricate\Console\Events\ScheduledBackgroundTaskFinished;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\NutsAndBolts\Collection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'schedule:finish')]
class ScheduleFinishCommand extends Command
{
    protected ?string $signature = 'schedule:finish {id} {code=0}';

    protected string $description = 'Handle the completion of a scheduled command';

    protected bool $hidden = true;

    public function handle(Schedule $schedule): void
    {
        (new Collection($schedule->events()))
            ->filter(fn ($value) => $value->mutexName() == $this->argument('id'))
            ->each(function ($event) {
                $event->finish($this->scrapyard_io, $this->argument('code'));

                $this->scrapyard_io->make(Dispatcher::class)->dispatch(new ScheduledBackgroundTaskFinished($event));
            });
    }
}
