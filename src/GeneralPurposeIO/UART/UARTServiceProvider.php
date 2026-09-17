<?php

namespace GeneralPurposeIO\UART;

use Voyager\Contracts\Vessel\Vessel;
use Voyager\NutsAndBolts\ServiceProvider;

class UARTServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('gpio.uart', fn (Vessel $app) => new UARTConnectionManager($app));
        $this->app->alias('gpio.uart', UARTConnectionManager::class);
    }

    public function boot(): void
    {

    }
}
