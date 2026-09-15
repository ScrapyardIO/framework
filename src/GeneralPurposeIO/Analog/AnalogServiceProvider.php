<?php

namespace GeneralPurposeIO\Analog;

use Voyager\Contracts\Vessel\Vessel;
use Voyager\NutsAndBolts\ServiceProvider;
use GeneralPurposeIO\Digital\DigitalInAdapterManager;
use GeneralPurposeIO\Digital\DigitalOutAdapterManager;
use Voyager\Contracts\NutsAndBolts\DeferrableProvider;
use Voyager\Contracts\Vessel\CircularDependencyException;

class AnalogServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        //$this->app->singleton('gpio.analog-in', fn(Vessel $program) => new DigitalInAdapterManager($program));
        //$this->app->alias('gpio.analog-in', DigitalInAdapterManager::class);

        //$this->app->singleton('gpio.analog-out', fn(Vessel $program) => new DigitalOutAdapterManager($program));
        //$this->app->alias('gpio.analog-out', DigitalOutAdapterManager::class);
    }

    /**
     * @throws CircularDependencyException
     */
    public function boot(): void
    {
        /*
        $adapters = config('gpio.protocols.analog-in.adapters');
        foreach ($adapters as $adapter => $adapter_class) {
            ADC::extend($adapter, fn() => new $adapter_class());
        }

        $adapters = config('gpio.protocols.analog-out.adapters');
        foreach ($adapters as $adapter => $adapter_class) {
            DAC::extend($adapter, fn() => new $adapter_class());
        }

        GPIO::extend('analog-in', fn() => app('gpio.analog-in'));
        GPIO::extend('analog-out', fn() => app('gpio.analog-out'));
        */
    }

    public function provides(): array
    {
        return ['gpio.analog-in', 'gpio.analog-in'];
    }
}