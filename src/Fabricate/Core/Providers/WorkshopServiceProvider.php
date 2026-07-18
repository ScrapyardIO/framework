<?php

namespace Fabricate\Core\Providers;

use Fabricate\Cache\Console\ClearCommand as CacheClearCommand;
use Fabricate\Cache\Console\ForgetCommand as CacheForgetCommand;
use Fabricate\Console\Signals;
use Fabricate\Contracts\Support\DeferrableProvider;
use Fabricate\Core\Console\AboutCommand;
use Fabricate\Core\Console\ConfigCacheCommand;
use Fabricate\Core\Console\ConfigClearCommand;
use Fabricate\Core\Console\ConfigShowCommand;
use Fabricate\Core\Console\ConsoleMakeCommand;
use Fabricate\Core\Console\EnvironmentCommand;
use Fabricate\Core\Console\PackageDiscoverCommand;
use Fabricate\NutsAndBolts\ServiceProvider;

class WorkshopServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected array $commands = [
        'About' => AboutCommand::class,
        'CacheClear' => CacheClearCommand::class,
        'CacheForget' => CacheForgetCommand::class,
        'ConfigCache' => ConfigCacheCommand::class,
        'ConfigClear' => ConfigClearCommand::class,
        'ConfigShow' => ConfigShowCommand::class,
        'Environment' => EnvironmentCommand::class,

        //'KeyGenerate' => KeyGenerateCommand::class,
        //'Optimize' => OptimizeCommand::class,
        //'OptimizeClear' => OptimizeClearCommand::class,
        'PackageDiscover' => PackageDiscoverCommand::class,
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected array $devCommands = [
        'ConsoleMake' => ConsoleMakeCommand::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerCommands(array_merge(
            $this->commands,
            $this->devCommands
        ));

        Signals::resolveAvailabilityUsing(function () {
            return $this->machine->runningInConsole()
                && ! $this->machine->runningUnitTests()
                && extension_loaded('pcntl');
        });
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        //DevCommands::registerDefaults();
    }

    /**
     * Register the given commands.
     *
     * @param array $commands
     * @return void
     */
    protected function registerCommands(array $commands): void
    {
        foreach ($commands as $commandName => $command) {
            $method = "register{$commandName}Command";

            if (method_exists($this, $method)) {
                $this->{$method}();
            } else {
                $this->machine->singleton($command);
            }
        }

        $this->commands(array_values($commands));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCacheClearCommand(): void
    {
        $this->machine->singleton(CacheClearCommand::class, function ($app) {
            return new CacheClearCommand($app['cache'], $app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerCacheForgetCommand(): void
    {
        $this->machine->singleton(CacheForgetCommand::class, function ($app) {
            return new CacheForgetCommand($app['cache']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return array_merge(array_values($this->commands), array_values($this->devCommands));
    }
}
