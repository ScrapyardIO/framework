<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'env')]
class EnvironmentCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected string $name = 'env';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Display the current framework environment';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->components->info(sprintf(
            'The application environment is [%s].',
            $this->scrapyard_io['env'],
        ));
    }
}
