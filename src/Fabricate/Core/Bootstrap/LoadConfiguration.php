<?php

namespace Fabricate\Core\Bootstrap;

use Closure;
use Exception;
use Fabricate\Config\Repository;
use Fabricate\Contracts\Core\Program;
use Fabricate\Contracts\Config\Repository as RepositoryInterface;
use Fabricate\NutsAndBolts\Collection;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class LoadConfiguration
{
    /**
     * The closure that resolves the permanent, static configuration if applicable.
     *
     * @var (Closure(Program): array<array-key, mixed>)|null
     */
    protected static ?Closure $alwaysUseConfig = null;

    /**
     * Bootstrap the given application.
     *
     * @param Program $program
     * @return void
     * @throws Exception
     */
    public function bootstrap(Program $program): void
    {
        $items = [];

        // First we will see if we have a cache configuration file. If we do, we'll load
        // the configuration items from that file so that it is very quick. Otherwise
        // we will need to spin through every configuration file and load them all.
        $loadedFromCache = false;

        if (self::$alwaysUseConfig !== null) {
            $items = $program->call(self::$alwaysUseConfig);

            $loadedFromCache = true;
        } elseif (file_exists($cached = $program->getCachedConfigPath())) {
            $items = require $cached;

            $loadedFromCache = true;
        }

        $program->instance('config_loaded_from_cache', $loadedFromCache);

        // Next we will spin through every configuration file in the configuration
        // directory and load each one into the repository. This will make all of the
        // options available to the developer for use in various parts of this machine.
        $program->instance('config', $config = new Repository($items));

        if (! $loadedFromCache) {
            $this->loadConfigurationFiles($program, $config);
        }

        // Finally, we will set the application's environment based on the configuration
        // values that were loaded. We will pass a callback which will be used to get
        // the environment in a web context where an "--env" switch is not present.
        $program->detectEnvironment(fn () => $config->get('machine.env', 'production'));

        $program->resolveEnvironmentUsing($program->environment(...));

        date_default_timezone_set($config->get('machine.timezone', 'UTC'));

        mb_internal_encoding('UTF-8');
    }

    /**
     * Load the configuration items from every file.
     *
     * @param  Program  $program
     * @param RepositoryInterface $repository
     * @return void
     *
     * @throws Exception
     */
    protected function loadConfigurationFiles(Program $program, RepositoryInterface $repository): void
    {
        $files = $this->getConfigurationFiles($program);

        $shouldMerge = method_exists($program, 'shouldMergeFrameworkConfiguration')
            ? $program->shouldMergeFrameworkConfiguration()
            : true;

        $base = $shouldMerge
            ? $this->getBaseConfiguration()
            : [];

        foreach (new Collection($base)->diffKeys($files) as $name => $config) {
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
     * @param RepositoryInterface $repository
     * @param  string  $name
     * @param  string  $path
     * @param  array  $base
     * @return array
     */
    protected function loadConfigurationFile(RepositoryInterface $repository, $name, $path, array $base)
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
     *
     * @param  string  $name
     * @return array
     */
    protected function mergeableOptions($name): array
    {
        return [
            //'auth' => ['guards', 'providers', 'passwords'],
            //'broadcasting' => ['connections'],
            //'cache' => ['stores'],
            'cache' => ['stores'],
            //'database' => ['connections'],
            'filesystems' => ['disks'],
            'logging' => ['channels'],
            //'mail' => ['mailers'],
            //'queue' => ['connections'],
            'redis' => ['clusters'],
            'sketches' => ['load'],
        ][$name] ?? [];
    }

    /**
     * Get every configuration file for the application.
     *
     * @param  Program  $program
     * @return array
     */
    protected function getConfigurationFiles(Program $program): array
    {
        $files = [];

        $configPath = realpath($program->configPath());

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
     *
     * @param  SplFileInfo  $file
     * @param string $configPath
     * @return string
     */
    protected function getNestedDirectory(SplFileInfo $file, string $configPath): string
    {
        $directory = $file->getPath();

        if ($nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
        }

        return $nested;
    }

    /**
     * Get the base configuration files.
     *
     * @return array
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
     * @param  (Closure(Program): array<array-key, mixed>)|null  $alwaysUseConfig
     * @return void
     */
    public static function alwaysUse(?Closure $alwaysUseConfig): void
    {
        static::$alwaysUseConfig = $alwaysUseConfig;
    }
}