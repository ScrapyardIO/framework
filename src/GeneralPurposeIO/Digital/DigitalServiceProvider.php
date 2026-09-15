<?php

namespace GeneralPurposeIO\Digital;

use Voyager\Contracts\Vessel\Vessel;
use Voyager\NutsAndBolts\ServiceProvider;
use Voyager\Contracts\NutsAndBolts\DeferrableProvider;
use Voyager\Contracts\Vessel\CircularDependencyException;

class DigitalServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton('gpio.digital-io', fn(Vessel $program) => new DigitalIOAdapterManager($program));
        $this->app->alias('gpio.digital-io', DigitalIOAdapterManager::class);
    }

    /**
     * @throws CircularDependencyException
     */
    public function boot(): void
    {
        $adapters = config('gpio.protocols.digital-io.adapters');
        foreach ($adapters as $adapter => $adapter_class) {
            DigitalIO::extend($adapter, fn() => new $adapter_class());
        }

        if ($this->app->bound('gpio')) {
            $this->app->make('gpio')->extend('digital-io', fn () => app('gpio.digital-io'));
        }
    }

    public function provides(): array
    {
        return ['gpio.digital-io'];
    }
}