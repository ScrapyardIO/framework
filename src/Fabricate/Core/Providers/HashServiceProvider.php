<?php

namespace Fabricate\Core\Providers;

use Fabricate\Contracts\Hashing\Hasher;
use Fabricate\Hashing\HashManager;
use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

/**
 * Binds HashManager as `hash`.
 *
 * Core owns this glue — not fabricate/hashing.
 */
class HashServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->container->singleton('hash', function ($app) {
            return new HashManager($app);
        });

        $this->container->singleton(HashManager::class, function ($app) {
            return $app->make('hash');
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'hash', HashManager::class, Hasher::class,
        ];
    }
}
