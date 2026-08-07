<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('env')]
class EnvironmentCommand extends Command
{
    /**
     * The console command name.
     */
    protected string $name = 'env';

    /**
     * The console command description.
     */
    protected string $description = 'Display the current framework environment';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info(sprintf(
            'The application environment is [%s].',
            $this->scrapyard_io['env'],
        ));

        return static::SUCCESS;
    }
}
