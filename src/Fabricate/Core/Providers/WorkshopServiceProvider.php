<?php

namespace Fabricate\Core\Providers;

use Fabricate\Concurrency\Console\InvokeSerializedClosureCommand;
use Fabricate\Cache\Console\ClearCommand as CacheClearCommand;
use Fabricate\Cache\Console\ForgetCommand as CacheForgetCommand;
use Fabricate\Console\Scheduling\ScheduleClearCacheCommand;
use Fabricate\Console\Scheduling\ScheduleFinishCommand;
use Fabricate\Console\Scheduling\ScheduleInterruptCommand;
use Fabricate\Console\Scheduling\ScheduleListCommand;
use Fabricate\Console\Scheduling\SchedulePauseCommand;
use Fabricate\Console\Scheduling\ScheduleResumeCommand;
use Fabricate\Console\Scheduling\ScheduleRunCommand;
use Fabricate\Console\Scheduling\ScheduleTestCommand;
use Fabricate\Console\Scheduling\ScheduleWorkCommand;
use Fabricate\Console\Signals;
use Fabricate\Core\Console\AboutCommand;
use Fabricate\Core\Console\ClassMakeCommand;
use Fabricate\Core\Console\ConfigCacheCommand;
use Fabricate\Core\Console\ConfigClearCommand;
use Fabricate\Core\Console\ConfigMakeCommand;
use Fabricate\Core\Console\ConfigShowCommand;
use Fabricate\Core\Console\ConsoleMakeCommand;
use Fabricate\Core\Console\EnumMakeCommand;
use Fabricate\Core\Console\EnvironmentCommand;
use Fabricate\Core\Console\EventCacheCommand;
use Fabricate\Core\Console\EventClearCommand;
use Fabricate\Core\Console\EventListCommand;
use Fabricate\Core\Console\EventMakeCommand;
use Fabricate\Core\Console\ExceptionMakeCommand;
use Fabricate\Core\Console\KeyGenerateCommand;
use Fabricate\Core\Console\ListenerMakeCommand;
use Fabricate\Core\Console\MiddlewareMakeCommand;
use Fabricate\Core\Console\ModelMakeCommand;
use Fabricate\Core\Console\NodeMakeCommand;
use Fabricate\Core\Console\ObserverMakeCommand;
use Fabricate\Core\Console\SketchMakeCommand;
use Fabricate\Core\Console\OptimizeClearCommand;
use Fabricate\Core\Console\OptimizeCommand;
use Fabricate\Core\Console\PackageDiscoverCommand;
use Fabricate\Core\Console\TraitMakeCommand;
use Fabricate\Core\Console\VendorPublishCommand;
use Fabricate\Database\Console\Migrations\InstallCommand as MigrateInstallCommand;
use Fabricate\Database\Console\Migrations\MigrateCommand;
use Fabricate\Database\Console\Migrations\MigrateMakeCommand;
use Fabricate\Database\Console\Migrations\RollbackCommand as MigrateRollbackCommand;
use Fabricate\Database\Console\Migrations\StatusCommand as MigrateStatusCommand;
use Fabricate\Database\Console\Seeds\SeedCommand;
use Fabricate\Database\Console\Seeds\SeederMakeCommand;
use Fabricate\Graph\Console\GraphModelMakeCommand;
use Fabricate\NutsAndBolts\Composer;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Queue\Console\RestartCommand as QueueRestartCommand;
use Fabricate\Queue\Console\WorkCommand as QueueWorkCommand;

/**
 * Registers Workshop CLI support (signals + framework commands as they return).
 *
 * Core owns this glue — not fabricate/console.
 * Not deferred while the command list is still thin (empty provides() would never load Signals).
 */
class WorkshopServiceProvider extends ServiceProvider
{
    /**
     * Framework commands keyed by register*Command method suffix when customized.
     *
     * @var array<string, class-string>
     */
    protected array $commands = [
        'About' => AboutCommand::class,
        'CacheClear' => CacheClearCommand::class,
        'CacheForget' => CacheForgetCommand::class,
        'ConfigCache' => ConfigCacheCommand::class,
        'ConfigClear' => ConfigClearCommand::class,
        'ConfigShow' => ConfigShowCommand::class,
        'Environment' => EnvironmentCommand::class,
        'EventCache' => EventCacheCommand::class,
        'EventClear' => EventClearCommand::class,
        'EventList' => EventListCommand::class,
        'KeyGenerate' => KeyGenerateCommand::class,
        'Migrate' => MigrateCommand::class,
        'MigrateInstall' => MigrateInstallCommand::class,
        'MigrateRollback' => MigrateRollbackCommand::class,
        'MigrateStatus' => MigrateStatusCommand::class,
        'Optimize' => OptimizeCommand::class,
        'OptimizeClear' => OptimizeClearCommand::class,
        'PackageDiscover' => PackageDiscoverCommand::class,
        'QueueRestart' => QueueRestartCommand::class,
        'QueueWork' => QueueWorkCommand::class,
        'Seed' => SeedCommand::class,
        'ScheduleClearCache' => ScheduleClearCacheCommand::class,
        'ScheduleFinish' => ScheduleFinishCommand::class,
        'ScheduleInterrupt' => ScheduleInterruptCommand::class,
        'ScheduleList' => ScheduleListCommand::class,
        'SchedulePause' => SchedulePauseCommand::class,
        'ScheduleResume' => ScheduleResumeCommand::class,
        'ScheduleRun' => ScheduleRunCommand::class,
        'ScheduleTest' => ScheduleTestCommand::class,
        'ScheduleWork' => ScheduleWorkCommand::class,
        'InvokeSerializedClosure' => InvokeSerializedClosureCommand::class,
    ];

    /**
     * Dev/generator commands.
     *
     * @var array<string, class-string>
     */
    protected array $devCommands = [
        'ClassMake' => ClassMakeCommand::class,
        'ConfigMake' => ConfigMakeCommand::class,
        'ConsoleMake' => ConsoleMakeCommand::class,
        'EnumMake' => EnumMakeCommand::class,
        'EventMake' => EventMakeCommand::class,
        'ExceptionMake' => ExceptionMakeCommand::class,
        'ListenerMake' => ListenerMakeCommand::class,
        'MiddlewareMake' => MiddlewareMakeCommand::class,
        'MigrateMake' => MigrateMakeCommand::class,
        'ModelMake' => ModelMakeCommand::class,
        'NodeMake' => NodeMakeCommand::class,
        'ObserverMake' => ObserverMakeCommand::class,
        'SeederMake' => SeederMakeCommand::class,
        'SketchMake' => SketchMakeCommand::class,
        'TraitMake' => TraitMakeCommand::class,
        'VendorPublish' => VendorPublishCommand::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $devCommands = $this->devCommands;

        // Graph ships in the umbrella; command is listed when the companion class exists.
        if (class_exists(GraphModelMakeCommand::class)) {
            $devCommands['GraphModelMake'] = GraphModelMakeCommand::class;
        }

        $this->registerCommands(array_merge(
            $this->commands,
            $devCommands
        ));

        Signals::resolveAvailabilityUsing(function () {
            $inConsole = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
            $testing = method_exists($this->container, 'runningUnitTests')
                && $this->container->runningUnitTests();

            return $inConsole && ! $testing && extension_loaded('pcntl');
        });
    }

    /**
     * Register the given commands.
     *
     * @param  array<string, class-string>  $commands
     * @return void
     */
    protected function registerCommands(array $commands): void
    {
        foreach ($commands as $commandName => $command) {
            $method = "register{$commandName}Command";

            if (method_exists($this, $method)) {
                $this->{$method}();
            } else {
                $this->container->singleton($command);
            }
        }

        $this->commands(array_values($commands));
    }

    /**
     * Register the about command.
     *
     * @return void
     */
    protected function registerAboutCommand(): void
    {
        $this->container->singleton(AboutCommand::class, function ($app) {
            return new AboutCommand(new Composer($app['files'], $app->basePath()));
        });
    }

    /**
     * Register the cache:clear command.
     *
     * @return void
     */
    protected function registerCacheClearCommand(): void
    {
        $this->container->singleton(CacheClearCommand::class, function ($app) {
            return new CacheClearCommand($app['cache'], $app['files']);
        });
    }

    /**
     * Register the cache:forget command.
     *
     * @return void
     */
    protected function registerCacheForgetCommand(): void
    {
        $this->container->singleton(CacheForgetCommand::class, function ($app) {
            return new CacheForgetCommand($app['cache']);
        });
    }

    /**
     * Register the config:cache command.
     *
     * @return void
     */
    protected function registerConfigCacheCommand(): void
    {
        $this->container->singleton(ConfigCacheCommand::class, function ($app) {
            return new ConfigCacheCommand($app['files']);
        });
    }

    /**
     * Register the config:clear command.
     *
     * @return void
     */
    protected function registerConfigClearCommand(): void
    {
        $this->container->singleton(ConfigClearCommand::class, function ($app) {
            return new ConfigClearCommand($app['files']);
        });
    }

    /**
     * Register the event:cache command.
     *
     * @return void
     */
    protected function registerEventCacheCommand(): void
    {
        $this->container->singleton(EventCacheCommand::class, function ($app) {
            return new EventCacheCommand($app['files']);
        });
    }

    /**
     * Register the event:clear command.
     *
     * @return void
     */
    protected function registerEventClearCommand(): void
    {
        $this->container->singleton(EventClearCommand::class, function ($app) {
            return new EventClearCommand($app['files']);
        });
    }

    /**
     * Register the schedule:interrupt command.
     *
     * @return void
     */
    protected function registerScheduleInterruptCommand(): void
    {
        $this->container->singleton(ScheduleInterruptCommand::class, function ($app) {
            return new ScheduleInterruptCommand($app['cache.store']);
        });
    }

    /**
     * Register the vendor:publish command.
     *
     * @return void
     */
    protected function registerVendorPublishCommand(): void
    {
        $this->container->singleton(VendorPublishCommand::class, function ($app) {
            return new VendorPublishCommand($app['files']);
        });
    }

    /**
     * Register the queue:work command.
     *
     * @return void
     */
    protected function registerQueueWorkCommand(): void
    {
        $this->container->singleton(QueueWorkCommand::class, function ($app) {
            return new QueueWorkCommand($app['queue.worker'], $app['cache.store']);
        });
    }

    /**
     * Register the queue:restart command.
     *
     * @return void
     */
    protected function registerQueueRestartCommand(): void
    {
        $this->container->singleton(QueueRestartCommand::class, function ($app) {
            return new QueueRestartCommand($app['cache.store']);
        });
    }

    protected function registerMigrateCommand(): void
    {
        $this->container->singleton(MigrateCommand::class, function ($app) {
            return new MigrateCommand(
                $app['migrator'],
                $app['events']
            );
        });
    }

    protected function registerMigrateInstallCommand(): void
    {
        $this->container->singleton(MigrateInstallCommand::class, function ($app) {
            return new MigrateInstallCommand($app['migration.repository']);
        });
    }

    protected function registerMigrateRollbackCommand(): void
    {
        $this->container->singleton(MigrateRollbackCommand::class, function ($app) {
            return new MigrateRollbackCommand($app['migrator']);
        });
    }

    protected function registerMigrateStatusCommand(): void
    {
        $this->container->singleton(MigrateStatusCommand::class, function ($app) {
            return new MigrateStatusCommand($app['migrator']);
        });
    }

    protected function registerMigrateMakeCommand(): void
    {
        $this->container->singleton(MigrateMakeCommand::class, function ($app) {
            return new MigrateMakeCommand(
                $app['migration.creator'],
                new Composer($app['files'], $app->basePath())
            );
        });
    }

    protected function registerSeedCommand(): void
    {
        $this->container->singleton(SeedCommand::class, function ($app) {
            return new SeedCommand($app['db']);
        });
    }
}
