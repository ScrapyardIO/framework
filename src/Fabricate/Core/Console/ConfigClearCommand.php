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
     */
    protected Filesystem $files;

    /**
     * Create a new config clear command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->files->delete($this->scrapyard_io->getCachedConfigPath());

        $this->components->info('Configuration cache cleared successfully.');

        return self::SUCCESS;
    }
}
