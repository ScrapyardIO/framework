<?php

namespace GeneralPurposeIO\Digital;

use Voyager\Contracts\Vessel\Vessel;
use Voyager\NutsAndBolts\ServiceProvider;

class DigitalIOServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('gpio.digital', fn (Vessel $app) => new DigitalOConnectionManager($app));
        $this->app->alias('gpio.digital', DigitalOConnectionManager::class);
    }

    public function boot(): void
    {

    }
}
