<?php

namespace GeneralPurposeIO\Digital;

use ReflectionException;
use Voyager\Contracts\Vessel\TheServiceContainer;
use Voyager\NutsAndBolts\ServiceProvider;

class DigitalIOServiceProvider extends ServiceProvider
{
    /**
     * @throws -ReflectionException
     */
    public function register(): void
    {
        $this->app->registerSingleton('gpio.digital', fn (TheServiceContainer $app) => new DigitalOConnectionManager($app));
        $this->app->alias('gpio.digital', DigitalOConnectionManager::class);
    }
}