<?php

namespace GeneralPurposeIO\PWM;

use Voyager\Contracts\Vessel\TheServiceContainer;
use Voyager\NutsAndBolts\ServiceProvider;

class PWMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->registerSingleton('gpio.pwm', fn (TheServiceContainer $app) => new PWMConnectionManager($app));
        $this->app->alias('gpio.pwm', PWMConnectionManager::class);
    }

    public function boot(): void
    {

    }
}
