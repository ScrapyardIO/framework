<?php

namespace GeneralPurposeIO\I2C;

use Voyager\Contracts\Vessel\CircularDependencyException;
use Voyager\Contracts\Vessel\Vessel;
use Voyager\Contracts\NutsAndBolts\DeferrableProvider;
use Voyager\NutsAndBolts\ServiceProvider;
use GeneralPurposeIO\I2C\Console\I2CDetectCommand;

class I2CServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton('gpio.i2c', fn (Vessel $program) => new I2CAdapterManager($program));
        $this->app->alias('gpio.i2c', I2CAdapterManager::class);

        $this->app->singleton(I2CDetectCommand::class);

        $this->commands([
            I2CDetectCommand::class,
        ]);
    }

    /**
     * @throws CircularDependencyException
     */
    public function boot(): void
    {
        $adapters = config('gpio.protocols.i2c.adapters');
        foreach ($adapters as $adapter => $adapter_class) {
            I2C::extend($adapter, fn () => new $adapter_class);
        }

        if ($this->app->bound('gpio')) {
            $this->app->make('gpio')->extend('i2c', fn () => app('gpio.i2c'));
        }
    }

    public function provides(): array
    {
        return [
            'gpio.i2c',
            I2CDetectCommand::class,
        ];
    }
}