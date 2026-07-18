<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Fabricate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'config:clear')]
class ConfigClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected string $name = 'config:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Remove the configuration cache file';

    /**
     * The filesystem instance.
     *
     * @var \Fabricate\Filesystem\Filesystem
     */
    protected Filesystem $files;

    /**
     * Create a new config clear command instance.
     *
     * @param  \Fabricate\Filesystem\Filesystem  $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->files->delete($this->scrapyard_io->getCachedConfigPath());

        $this->components->info('Configuration cache cleared successfully.');
    }
}
