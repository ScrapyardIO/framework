<?php

namespace Fabricate\Core\Providers;

use Fabricate\Concurrency\ConcurrencyManager;
use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

/**
 * Binds ConcurrencyManager as `concurrency`.
 *
 * Core owns this glue — not fabricate/concurrency.
 */
class ConcurrencyServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->container->singleton('concurrency', function ($app) {
            return new ConcurrencyManager($app);
        });

        $this->container->singleton(ConcurrencyManager::class, function ($app) {
            return $app->make('concurrency');
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['concurrency', ConcurrencyManager::class];
    }
}
