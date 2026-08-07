<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Fabricate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'event:clear')]
class EventClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected string $name = 'event:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Clear all cached events and listeners';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Create a new event clear command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->files->delete($this->scrapyard_io->getCachedEventsPath());

        $this->components->info('Cached events cleared successfully.');
    }
}
