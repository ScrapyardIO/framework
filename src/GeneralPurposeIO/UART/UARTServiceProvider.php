<?php

namespace GeneralPurposeIO\UART;

use Voyager\Contracts\Vessel\TheServiceContainer;
use Voyager\NutsAndBolts\ServiceProvider;

class UARTServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->registerSingleton('gpio.uart', fn (TheServiceContainer $app) => new UARTConnectionManager($app));
        $this->app->alias('gpio.uart', UARTConnectionManager::class);
    }

    public function boot(): void
    {

    }
}
