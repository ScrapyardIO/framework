<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\NutsAndBolts\Stringable;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'optimize:clear')]
class OptimizeClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected ?string $signature = 'optimize:clear {--e|except= : The commands to skip}';

    /**
     * The console command description.
     */
    protected string $description = 'Remove the cached bootstrap files';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->components->info('Clearing cached bootstrap files.');

        $exceptions = (new Stringable($this->option('except') ?? ''))->explode(',')
            ->map(fn ($except) => trim($except))
            ->filter()
            ->unique()
            ->flip();

        $tasks = Collection::wrap($this->getOptimizeClearTasks())
            ->reject(fn ($command, $key) => $exceptions->hasAny([$command, $key]))
            ->all();

        foreach ($tasks as $description => $command) {
            $this->components->task($description, fn () => $this->callSilently($command) === 0);
        }

        $this->newLine();
    }

    /**
     * Get the commands that should be run to clear optimization files.
     *
     * Only restored 0.7 surfaces are listed (no route/view/clear-compiled yet).
     *
     * @return array<string, string>
     */
    public function getOptimizeClearTasks(): array
    {
        return [
            'config' => 'config:clear',
            'cache' => 'cache:clear',
            'events' => 'event:clear',
            ...ServiceProvider::$optimizeClearCommands,
        ];
    }
}
