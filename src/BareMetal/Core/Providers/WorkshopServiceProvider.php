<?php

namespace BareMetal\Core\Providers;

use BareMetal\Console\Signals;
use BareMetal\Core\Console\EnvironmentCommand;
use ScrapyardIO\NutsAndBolts\ServiceProvider;
use BareMetal\Contracts\Support\DeferrableProvider;

class WorkshopServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected array $commands = [
        'Environment' => EnvironmentCommand::class,
    ];

    protected array $dev_commands = [];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerCommands(array_merge(
            $this->commands,
            $this->dev_commands
        ));

        Signals::resolveAvailabilityUsing(function () {
            return $this->app->runningInConsole()
                && ! $this->app->runningUnitTests()
                && extension_loaded('pcntl');
        });
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {

    }

    /**
     * Register the given commands.
     */
    protected function registerCommands(array $commands): void
    {
        foreach ($commands as $commandName => $command) {
            $method = "register{$commandName}Command";

            if (method_exists($this, $method)) {
                $this->{$method}();
            } else {
                $this->app->singleton($command);
            }
        }

        $this->commands(array_values($commands));
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return array_merge(array_values($this->commands), array_values($this->dev_commands));
    }
}
