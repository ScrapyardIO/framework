<?php

namespace GeneralPurposeIO\UART;

use Voyager\Contracts\Vessel\CircularDependencyException;
use Voyager\Contracts\Vessel\Vessel;
use Voyager\NutsAndBolts\ServiceProvider;
use Voyager\Contracts\NutsAndBolts\DeferrableProvider;

class UARTServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton('gpio.uart', fn(Vessel $program) => new UARTAdapterManager($program));
        $this->app->alias('gpio.uart', UARTAdapterManager::class);
    }

    /**
     * @throws CircularDependencyException
     */
    public function boot(): void
    {
        $adapters = config('gpio.protocols.uart.adapters');
        foreach ($adapters as $adapter => $adapter_class) {
            UART::extend($adapter, fn() => new $adapter_class());
        }

        if ($this->app->bound('gpio')) {
            $this->app->make('gpio')->extend('uart', fn () => app('gpio.uart'));
        }
    }

    public function provides(): array
    {
        return ['gpio.uart'];
    }
}