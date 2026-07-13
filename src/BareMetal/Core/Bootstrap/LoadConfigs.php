<?php

namespace BareMetal\Core\Bootstrap;

use Closure;
use Exception;
use Illuminate\Support\Collection;
use SplFileInfo;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Config\Repository as RepositoryContract;
use BareMetal\Contracts\Core\Application as ScrapyardAppInterface;
use Symfony\Component\Finder\Finder;

class LoadConfigs
{
    protected static ?Closure $always_use_config = null;

    /**
     * Bootstrap the given application.
     * @throws Exception
     */
    public function bootstrap(ScrapyardAppInterface $app): void
    {
        $items = [];

        // First we will see if we have a cache configuration file. If we do, we'll load
        // the configuration items from that file so that it is very quick. Otherwise,
        // we will need to spin through every configuration file and load them all.
        $loaded_from_cache = false;

        if (self::$always_use_config !== null) {
            $items = $app->call(self::$always_use_config);

            $loaded_from_cache = true;
        } elseif (file_exists($cached = $app->getCachedConfigPath())) {
            $items = require $cached;

            $loaded_from_cache = true;
        }

        $app->instance('config_loaded_from_cache', $loaded_from_cache);

        // Next we will spin through all of the configuration files in the configuration
        // directory and load each one into the repository. This will make all of the
        // options available to the developer for use in various parts of this app.
        $app->instance('config', $config = new Repository($items));

        if (! $loaded_from_cache) {
            $this->loadConfigurationFiles($app, $config);
        }

        // Finally, we will set the application's environment based on the configuration
        // values that were loaded. We will pass a callback which will be used to get
        // the environment in a web context where an "--env" switch is not present.
        $app->detectEnvironment(fn () => $config->get('scrapyard.env', 'production'));

        $app->resolveEnvironmentUsing($app->environment(...));

        date_default_timezone_set($config->get('scrapyard.timezone', 'UTC'));

        mb_internal_encoding('UTF-8');
    }

    /**
     * Load the configuration items from every file.
     * @throws Exception
     */
    protected function loadConfigurationFiles(ScrapyardAppInterface $app, RepositoryContract $repository): void
    {
        $files = $this->getConfigurationFiles($app);

        $shouldMerge = method_exists($app, 'shouldMergeFrameworkConfiguration')
            ? $app->shouldMergeFrameworkConfiguration()
            : true;

        $base = $shouldMerge
            ? $this->getBaseConfiguration()
            : [];

        foreach ((new Collection($base))->diffKeys($files) as $name => $config) {
            $repository->set($name, $config);
        }

        foreach ($files as $name => $path) {
            $base = $this->loadConfigurationFile($repository, $name, $path, $base);
        }

        foreach ($base as $name => $config) {
            $repository->set($name, $config);
        }
    }

    /**
     * Load the given configuration file.
     */
    protected function loadConfigurationFile(RepositoryContract $repository, string $name, string $path, array $base): array
    {
        $config = (fn () => require $path)();

        if (isset($base[$name])) {
            $config = array_merge($base[$name], $config);

            foreach ($this->mergeableOptions($name) as $option) {
                if (isset($config[$option])) {
                    $config[$option] = array_merge($base[$name][$option], $config[$option]);
                }
            }

            unset($base[$name]);
        }

        $repository->set($name, $config);

        return $base;
    }

    /**
     * Get the options within the configuration file that should be merged again.
     */
    protected function mergeableOptions(string $name): array
    {
        return [
            //'auth' => ['guards', 'providers', 'passwords'],
            //'broadcasting' => ['connections'],
            'cache' => ['stores'],
            //'database' => ['connections'],
            'filesystems' => ['disks'],
            //'logging' => ['channels'],
            //'mail' => ['mailers'],
            //'queue' => ['connections'],
        ][$name] ?? [];
    }

    /**
     * Get every configuration file for the application.
     */
    protected function getConfigurationFiles(ScrapyardAppInterface $app): array
    {
        $files = [];

        $configPath = realpath($app->configPath());

        if (! $configPath) {
            return [];
        }

        foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            $directory = $this->getNestedDirectory($file, $configPath);

            $files[$directory.basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }

    /**
     * Get the configuration file nesting path.
     */
    protected function getNestedDirectory(SplFileInfo $file, string $config_path): string
    {
        $directory = $file->getPath();

        if ($nested = trim(str_replace($config_path, '', $directory), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
        }

        return $nested;
    }

    /**
     * Get the base configuration files.
     */
    protected function getBaseConfiguration(): array
    {
        $config = [];

        foreach (Finder::create()->files()->name('*.php')->in(__DIR__.'/../../../../config') as $file) {
            $config[basename($file->getRealPath(), '.php')] = require $file->getRealPath();
        }

        return $config;
    }

    /**
     * Set a callback to return the permanent, static configuration values.
     *
     * @param  (Closure(ScrapyardAppInterface): array<array-key, mixed>)|null  $always_use_config
     */
    public static function alwaysUse(?Closure $always_use_config): void
    {
        static::$always_use_config = $always_use_config;
    }
}
