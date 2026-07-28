<?php

namespace Fabricate\Core\Providers;

use ReflectionException;
use Fabricate\Console\Signals;
use Fabricate\Core\DevCommands;
use Fabricate\Core\Console\AboutCommand;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Core\Console\ConfigShowCommand;
use Fabricate\Core\Console\ConfigCacheCommand;
use Fabricate\Core\Console\ConfigClearCommand;
use Fabricate\Core\Console\ClassMakeCommand;
use Fabricate\Core\Console\ConfigMakeCommand;
use Fabricate\Core\Console\ConsoleMakeCommand;
use Fabricate\Core\Console\EnumMakeCommand;
use Fabricate\Core\Console\EnvironmentCommand;
use Fabricate\Core\Console\EventMakeCommand;
use Fabricate\Core\Console\ExceptionMakeCommand;
use Fabricate\Core\Console\FramebufferMakeCommand;
use Fabricate\Core\Console\InstallFontsCommand;
use Fabricate\Core\Console\InstallGfxCommand;
use Fabricate\Core\Console\InstallGpioCommand;
use Fabricate\Core\Console\InstallSensorsCommand;
use Fabricate\Core\Console\InterfaceMakeCommand;
use Fabricate\Core\Console\JobMakeCommand;
use Fabricate\Core\Console\KeyGenerateCommand;
use Fabricate\Core\Console\ListenerMakeCommand;
use Fabricate\Core\Console\ObserverMakeCommand;
use Fabricate\Core\Console\PackageDiscoverCommand;
use Fabricate\Core\Console\ProviderMakeCommand;
use Fabricate\Core\Console\SketchMakeCommand;
use Fabricate\Core\Console\TraitMakeCommand;
use Fabricate\Core\Console\VendorPublishCommand;
use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;
use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Cache\Console\ClearCommand as CacheClearCommand;
use Fabricate\Cache\Console\ForgetCommand as CacheForgetCommand;

class WorkshopServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected array $commands = [
        'About' => AboutCommand::class,
        'CacheClear' => CacheClearCommand::class,
        'CacheForget' => CacheForgetCommand::class,
        'ConfigCache' => ConfigCacheCommand::class,
        'ConfigClear' => ConfigClearCommand::class,
        'ConfigShow' => ConfigShowCommand::class,
        'Environment' => EnvironmentCommand::class,
        'KeyGenerate' => KeyGenerateCommand::class,
        //'Optimize' => OptimizeCommand::class,
        //'OptimizeClear' => OptimizeClearCommand::class,
        'PackageDiscover' => PackageDiscoverCommand::class,
        /*
        'QueueClear' => QueueClearCommand::class,
        'QueueFailed' => ListFailedQueueCommand::class,
        'QueueFlush' => FlushFailedQueueCommand::class,
        'QueueForget' => ForgetFailedQueueCommand::class,
        'QueueListen' => QueueListenCommand::class,
        'QueueMonitor' => QueueMonitorCommand::class,
        'QueuePause' => QueuePauseCommand::class,
        'QueuePruneBatches' => QueuePruneBatchesCommand::class,
        'QueuePruneFailedJobs' => QueuePruneFailedJobsCommand::class,
        'QueueRestart' => QueueRestartCommand::class,
        'QueueResume' => QueueResumeCommand::class,
        'QueueRetry' => QueueRetryCommand::class,
        'QueueRetryBatch' => QueueRetryBatchCommand::class,
        'QueueWork' => QueueWorkCommand::class,*/
        /*
         ScheduleFinish' => ScheduleFinishCommand::class,
        'ScheduleList' => ScheduleListCommand::class,
        'ScheduleRun' => ScheduleRunCommand::class,
        'ScheduleClearCache' => ScheduleClearCacheCommand::class,
        'ScheduleTest' => ScheduleTestCommand::class,
        'ScheduleWork' => ScheduleWorkCommand::class,
        'ScheduleInterrupt' => ScheduleInterruptCommand::class,
        'SchedulePause' => SchedulePauseCommand::class,
        'ScheduleResume' => ScheduleResumeCommand::class,
         */
    ];

    protected array $devCommands = [
        //'CacheTable' => CacheTableCommand::class,
        'ClassMake' => ClassMakeCommand::class,
        'ConfigMake' => ConfigMakeCommand::class,
        //'ConfigPublish' => ConfigPublishCommand::class,
        'ConsoleMake' => ConsoleMakeCommand::class,
        'EnumMake' => EnumMakeCommand::class,
        //'EventGenerate' => EventGenerateCommand::class,
        'EventMake' => EventMakeCommand::class,
        'ExceptionMake' => ExceptionMakeCommand::class,
        'FramebufferMake' => FramebufferMakeCommand::class,
        'InstallFonts' => InstallFontsCommand::class,
        'InstallGfx' => InstallGfxCommand::class,
        'InstallGpio' => InstallGpioCommand::class,
        'InstallSensors' => InstallSensorsCommand::class,
        'InterfaceMake' => InterfaceMakeCommand::class,
        'JobMake' => JobMakeCommand::class,
        'ListenerMake' => ListenerMakeCommand::class,
        'ObserverMake' => ObserverMakeCommand::class,
        'ProviderMake' => ProviderMakeCommand::class,
        //'QueueFailedTable' => FailedTableCommand::class,
        //'QueueTable' => TableCommand::class,
        //'QueueBatchesTable' => BatchesTableCommand::class,
        'SketchMake' => SketchMakeCommand::class,
        //'StubPublish' => StubPublishCommand::class,
        //'TestMake' => TestMakeCommand::class,
        'TraitMake' => TraitMakeCommand::class,
        'VendorPublish' => VendorPublishCommand::class,

        //'DaemonMake'
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
            return $this->program->runningInConsole()
                && (! $this->program->runningUnitTests())
                && extension_loaded('pcntl');
        });
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     * @throws ReflectionException|CircularDependencyException|BindingResolutionException
     */
    public function boot(): void
    {
        DevCommands::registerDefaults();
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
                $this->program->singleton($command);
            }
        }

        $this->commands(array_values($commands));
    }

    /**
     * Register the cache:clear command.
     *
     * @return void
     */
    protected function registerCacheClearCommand(): void
    {
        $this->program->singleton(CacheClearCommand::class, function ($app) {
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
        $this->program->singleton(CacheForgetCommand::class, function ($app) {
            return new CacheForgetCommand($app['cache']);
        });
    }

    /**
     * Register the vendor:publish command.
     *
     * @return void
     */
    protected function registerVendorPublishCommand(): void
    {
        $this->program->singleton(VendorPublishCommand::class, function ($app) {
            return new VendorPublishCommand($app['files']);
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
