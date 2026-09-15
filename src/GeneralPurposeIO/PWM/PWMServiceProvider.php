<?php

namespace GeneralPurposeIO\PWM;

use Voyager\Contracts\Vessel\CircularDependencyException;
use Voyager\Contracts\Vessel\Vessel;
use Voyager\NutsAndBolts\ServiceProvider;
use Voyager\Contracts\NutsAndBolts\DeferrableProvider;

class PWMServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton('gpio.pwm', fn(Vessel $program) => new PWMAdapterManager($program));
        $this->app->alias('gpio.pwm', PWMAdapterManager::class);
    }

    /**
     * @throws CircularDependencyException
     */
    public function boot(): void
    {
        $adapters = config('gpio.protocols.pwm.adapters');
        foreach ($adapters as $adapter => $adapter_class) {
            PWM::extend($adapter, fn() => new $adapter_class());
        }

        if ($this->app->bound('gpio')) {
            $this->app->make('gpio')->extend('pwm', fn () => app('gpio.pwm'));
        }
    }

    public function provides(): array
    {
        return ['gpio.pwm'];
    }
}