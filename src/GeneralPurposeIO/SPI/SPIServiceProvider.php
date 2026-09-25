<?php

namespace GeneralPurposeIO\SPI;

use Voyager\Contracts\Vessel\TheServiceContainer;
use Voyager\NutsAndBolts\ServiceProvider;

class SPIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->registerSingleton('gpio.spi', fn (TheServiceContainer $app) => new SPIConnectionManager($app));
        $this->app->alias('gpio.spi', SPIConnectionManager::class);
    }
}
