<?php

namespace GeneralPurposeIO\IntegratedCircuits;

use Voyager\NutsAndBolts\ServiceProvider;
use Voyager\Contracts\Core\FrameworkCore;
use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitRegistry as RegistryContract;

/**
 * Binds the one catalog. Chip packages fill it from their own boot() and own
 * their own config under circuits.<slug>; this provider knows no chip.
 */
class IntegratedCircuitsServiceProvider extends ServiceProvider
{
    /**
     * @throws \ReflectionException
     */
    public function register(): void
    {
        $this->app->registerSingleton('circuit', fn (FrameworkCore $app) => new CircuitRegistry);
        $this->app->alias('circuit', CircuitRegistry::class);
        $this->app->alias('circuit', RegistryContract::class);
    }
}
