<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Fabricate\Contracts\Console\CLIKernel;
use Fabricate\Filesystem\Filesystem;
use Fabricate\NutsAndBolts\Arr;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'config:cache')]
class ConfigCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected string $name = 'config:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Create a cache file for faster configuration loading';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Create a new config cache command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @throws \LogicException
     */
    public function handle(): int
    {
        $this->callSilent('config:clear');

        $config = $this->getFreshConfiguration();

        $configPath = $this->scrapyard_io->getCachedConfigPath();

        $this->files->ensureDirectoryExists(dirname($configPath));

        $this->files->put(
            $configPath, '<?php return '.var_export($config, true).';'.PHP_EOL
        );

        try {
            require $configPath;
        } catch (Throwable $e) {
            $this->files->delete($configPath);

            foreach (Arr::dot($config) as $key => $value) {
                try {
                    eval(var_export($value, true).';');
                } catch (Throwable $nested) {
                    throw new LogicException("Your configuration files could not be serialized because the value at \"{$key}\" is non-serializable.", 0, $nested);
                }
            }

            throw new LogicException('Your configuration files are not serializable.', 0, $e);
        }

        $this->components->info('Configuration cached successfully.');

        return self::SUCCESS;
    }

    /**
     * Boot a fresh copy of the application configuration.
     *
     * @return array<string, mixed>
     */
    protected function getFreshConfiguration(): array
    {
        $app = require $this->scrapyard_io->bootstrapPath('app.php');

        $app->useStoragePath($this->scrapyard_io->storagePath());

        $app->make(CLIKernel::class)->bootstrap();

        return $app['config']->all();
    }
}
