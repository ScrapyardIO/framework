<?php

namespace Fabricate\Core\Providers;

use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Process\Factory;

/**
 * Binds the Process Factory as `process`.
 *
 * Core owns this glue — not fabricate/process.
 */
class ProcessServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->container->singleton('process', function () {
            return new Factory;
        });

        $this->container->singleton(Factory::class, function ($app) {
            return $app->make('process');
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['process', Factory::class];
    }
}
