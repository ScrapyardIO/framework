<?php

namespace GeneralPurposeIO\SPI;

use Voyager\Contracts\Vessel\Vessel;
use Voyager\NutsAndBolts\ServiceProvider;

class SPIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('gpio.spi', fn (Vessel $app) => new SPIConnectionManager($app));
        $this->app->alias('gpio.spi', SPIConnectionManager::class);
    }

    public function boot(): void
    {

    }
}
