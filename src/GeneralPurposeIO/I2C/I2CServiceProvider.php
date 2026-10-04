<?php

namespace GeneralPurposeIO\I2C;

use Voyager\Contracts\Vessel\TheServiceContainer;
use Voyager\NutsAndBolts\ServiceProvider;

class I2CServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->registerSingleton('gpio.i2c', fn (TheServiceContainer $app) => new I2CConnectionManager($app));
        $this->app->alias('gpio.i2c', I2CConnectionManager::class);
    }
}
