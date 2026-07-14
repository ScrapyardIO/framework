<?php

namespace BareMetal\Core\Bootstrap;

use Closure;
use Exception;
use SplFileInfo;
use BareMetal\Config\Repository;
use Symfony\Component\Finder\Finder;
use BareMetal\Contracts\Core\Machine;
use ScrpayardIO\NutsAndBolts\Collection;
use BareMetal\Contracts\Config\Repository as RepositoryContract;

class LoadConfiguration
{
    /**
     * The closure that resolves the permanent, static configuration if applicable.
     *
     * @var (Closure(Application): array<array-key, mixed>)|null
     */
    protected static ?Closure $alwaysUseConfig = null;

    /**
     * Bootstrap the given application.
     * @throws Exception
     */
    public function bootstrap(Machine $app): void
    {
        $items = [];

        // First we will see if we have a cache configuration file. If we do, we'll load
        // the configuration items from that file so that it is very quick. Otherwise,
        // we will need to spin through every configuration file and load them all.
        $loadedFromCache = false;

        if (self::$alwaysUseConfig !== null) {
            $items = $app->call(self::$alwaysUseConfig);

            $loadedFromCache = true;
        } elseif (file_exists($cached = $app->getCachedConfigPath())) {
            $items = require $cached;

            $loadedFromCache = true;
        }

        $app->instance('config_loaded_from_cache', $loadedFromCache);

        // Next we will spin through every configuration file in the configuration
        // directory and load each one into the repository. This will make every
        // option available to the developer for use in various parts of this app.
        $app->instance('config', $config = new Repository($items));
        if (! $loadedFromCache) {
            $this->loadConfigurationFiles($app, $config);
        }

        // Finally, we will set the application's environment based on the configuration
        // values that were loaded. We will pass a callback which will be used to get
        // the environment in a web context where an "--env" switch is not present.
        $app->detectEnvironment(fn () => $config->get('scrapyard-io.env', 'production'));

        $app->resolveEnvironmentUsing($app->environment(...));

        date_default_timezone_set($config->get('scrapyard-io.timezone', 'UTC'));

        mb_internal_encoding('UTF-8');
    }

    /**
     * Load the configuration items from every file.
     * @throws Exception
     */
    protected function loadConfigurationFiles(Machine $app, RepositoryContract $repository): void
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
     *
     * @param  \Illuminate\Contracts\Config\Repository  $repository
     * @param  string  $name
     * @param  string  $path
     * @param  array  $base
     * @return array
     */
    protected function loadConfigurationFile(RepositoryContract $repository, $name, $path, array $base)
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
    protected function getConfigurationFiles(Machine $app): array
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
     */
    public static function alwaysUse(?Closure $alwaysUseConfig): void
    {
        static::$alwaysUseConfig = $alwaysUseConfig;
    }
}
