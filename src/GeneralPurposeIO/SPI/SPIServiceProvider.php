<?php

namespace GeneralPurposeIO\SPI;

use Voyager\Contracts\Vessel\CircularDependencyException;
use Voyager\Contracts\Vessel\Vessel;
use Voyager\NutsAndBolts\ServiceProvider;
use Voyager\Contracts\NutsAndBolts\DeferrableProvider;

class SPIServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton('gpio.spi', fn(Vessel $program) => new SPIAdapterManager($program));
        $this->app->alias('gpio.spi', SPIAdapterManager::class);
    }

    /**
     * @throws CircularDependencyException
     */
    public function boot(): void
    {
        $adapters = config('gpio.protocols.spi.adapters');
        foreach ($adapters as $adapter => $adapter_class) {
            SPI::extend($adapter, fn() => new $adapter_class());
        }

        if ($this->app->bound('gpio')) {
            $this->app->make('gpio')->extend('spi', fn () => app('gpio.spi'));
        }
    }

    public function provides(): array
    {
        return ['gpio.spi'];
    }
}