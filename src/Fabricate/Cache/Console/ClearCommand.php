<?php

namespace Fabricate\Cache\Console;

use BadMethodCallException;
use Fabricate\Cache\CacheManager;
use Fabricate\Console\Command;
use Fabricate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'cache:clear')]
class ClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected string $name = 'cache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Flush the application cache';

    /**
     * The cache manager instance.
     *
     * @var \Fabricate\Cache\CacheManager
     */
    protected CacheManager $cache;

    /**
     * The filesystem instance.
     *
     * @var \Fabricate\Filesystem\Filesystem
     */
    protected Filesystem $files;

    /**
     * Create a new cache clear command instance.
     *
     * @param  \Fabricate\Cache\CacheManager  $cache
     * @param  \Fabricate\Filesystem\Filesystem  $files
     */
    public function __construct(CacheManager $cache, Filesystem $files)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if ($this->option('locks')) {
            return $this->clearLocks();
        }

        $this->scrapyard_io['events']->dispatch(
            'cache:clearing', [$this->argument('store'), $this->tags()]
        );

        $successful = $this->cache()->flush();

        $this->flushMagicAliases();

        if (! $successful) {
            $this->components->error('Failed to clear cache. Make sure you have the appropriate permissions.');

            return self::FAILURE;
        }

        $this->scrapyard_io['events']->dispatch(
            'cache:cleared', [$this->argument('store'), $this->tags()]
        );

        $this->components->info('Application cache cleared successfully.');

        return self::SUCCESS;
    }

    /**
     * Clear all locks from the cache store.
     *
     * @return int
     */
    protected function clearLocks(): int
    {
        if (! empty($this->tags())) {
            $this->components->error('Cache tags cannot be used when clearing locks.');

            return self::FAILURE;
        }

        try {
            $successful = $this->cache()->flushLocks();
        } catch (BadMethodCallException) {
            $this->components->error('This cache store does not support clearing locks.');

            return self::FAILURE;
        }

        if (! $successful) {
            $this->components->error('Failed to clear cache locks. Make sure you have the appropriate permissions.');

            return self::FAILURE;
        }

        $this->components->info('Application cache locks cleared successfully.');

        return self::SUCCESS;
    }

    /**
     * Flush the real-time magic aliases stored in the cache directory.
     *
     * @return void
     */
    public function flushMagicAliases(): void
    {
        if (! $this->files->exists($storagePath = storage_path('framework/cache'))) {
            return;
        }

        foreach ($this->files->files($storagePath) as $file) {
            if (preg_match('/magic-alias-.*\.php$/', $file->getFilename())) {
                $this->files->delete($file->getPathname());
            }
        }
    }

    /**
     * Get the cache instance for the command.
     *
     * @return \Fabricate\Cache\CacheRepository
     */
    protected function cache()
    {
        $cache = $this->cache->store($this->argument('store'));

        return empty($this->tags()) ? $cache : $cache->tags($this->tags());
    }

    /**
     * Get the tags passed to the command.
     *
     * @return array
     */
    protected function tags(): array
    {
        return array_filter(explode(',', $this->option('tags') ?? ''));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['store', InputArgument::OPTIONAL, 'The name of the store you would like to clear'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['tags', null, InputOption::VALUE_OPTIONAL, 'The cache tags you would like to clear', null],
            ['locks', null, InputOption::VALUE_NONE, 'Only clear cache locks'],
        ];
    }
}
