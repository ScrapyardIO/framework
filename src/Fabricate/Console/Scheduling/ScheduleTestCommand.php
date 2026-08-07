<?php

namespace Fabricate\Console\Scheduling;

use Fabricate\Console\Command;
use Fabricate\Console\WorkshopInstance;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\select;

#[AsCommand(name: 'schedule:test')]
class ScheduleTestCommand extends Command
{
    protected ?string $signature = 'schedule:test {--name= : The name of the scheduled command to run}';

    protected string $description = 'Run a scheduled command';

    public function handle(Schedule $schedule): void
    {
        $phpBinary = WorkshopInstance::phpBinary();

        $commands = $schedule->events();

        $commandNames = [];

        foreach ($commands as $command) {
            $commandNames[] = $command->command ?? $command->getSummaryForDisplay();
        }

        if (empty($commandNames)) {
            $this->components->info('No scheduled commands have been defined.');

            return;
        }

        if (! empty($name = $this->option('name'))) {
            $commandBinary = $phpBinary.' '.WorkshopInstance::workshopBinary();

            $matches = array_filter($commandNames, function ($commandName) use ($commandBinary, $name) {
                return trim(str_replace($commandBinary, '', $commandName)) === $name;
            });

            if (count($matches) !== 1) {
                $this->components->info('No matching scheduled command found.');

                return;
            }

            $index = key($matches);
        } else {
            $index = $this->getSelectedCommandByIndex($commandNames);
        }

        $event = $commands[$index];

        $summary = $event->getSummaryForDisplay();

        $command = $event instanceof CallbackEvent
            ? $summary
            : trim(str_replace($phpBinary, '', $event->command));

        $description = sprintf(
            'Running [%s]%s',
            $command,
            $event->runInBackground ? ' normally in background' : '',
        );

        $event->runInBackground = false;

        $this->components->task($description, fn () => $event->run($this->scrapyard_io));

        if (! $event instanceof CallbackEvent) {
            $this->components->bulletList([$event->getSummaryForDisplay()]);
        }

        $this->newLine();
    }

    protected function getSelectedCommandByIndex(array $commandNames): int
    {
        if (count($commandNames) !== count(array_unique($commandNames))) {
            $uniqueCommandNames = array_map(function ($index, $value) {
                return "$value [$index]";
            }, array_keys($commandNames), $commandNames);

            $selectedCommand = select('Which command would you like to run?', $uniqueCommandNames);

            preg_match('/\[(\d+)\]/', $selectedCommand, $choice);

            return (int) $choice[1];
        }

        return array_search(
            select('Which command would you like to run?', $commandNames),
            $commandNames
        );
    }
}
