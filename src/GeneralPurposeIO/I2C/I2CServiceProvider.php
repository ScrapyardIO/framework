<?php

namespace GeneralPurposeIO\I2C;

use Voyager\Contracts\Vessel\Vessel;
use Voyager\NutsAndBolts\ServiceProvider;

class I2CServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('gpio.i2c', fn (Vessel $app) => new I2CConnectionManager($app));
        $this->app->alias('gpio.i2c', I2CConnectionManager::class);
    }

    public function boot(): void
    {

    }
}
