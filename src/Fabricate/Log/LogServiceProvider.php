<?php

namespace Fabricate\Log;

use Fabricate\NutsAndBolts\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->machine->singleton('log', fn ($app) => new LogManager($app));

        $this->machine->alias('log', LogManager::class);
        $this->machine->alias('log', \Psr\Log\LoggerInterface::class);
    }
}
