<?php

namespace GeneralPurposeIO\PWM;

use Voyager\Contracts\Vessel\Vessel;
use Voyager\NutsAndBolts\ServiceProvider;

class PWMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('gpio.pwm', fn (Vessel $app) => new PWMConnectionManager($app));
        $this->app->alias('gpio.pwm', PWMConnectionManager::class);
    }

    public function boot(): void
    {

    }
}
