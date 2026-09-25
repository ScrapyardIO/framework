<?php

namespace GeneralPurposeIO\IntegratedCircuits;

use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitRegistry as RegistryContract;
use Voyager\Contracts\Vessel\Vessel;
use Voyager\NutsAndBolts\ServiceProvider;

/**
 * Binds the one catalog. Chip packages fill it from their own boot() and own
 * their own config under circuits.<slug>; this provider knows no chip.
 */
class IntegratedCircuitsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('circuit', fn (Vessel $app) => new CircuitRegistry);
        $this->app->alias('circuit', CircuitRegistry::class);
        $this->app->alias('circuit', RegistryContract::class);
    }
}
