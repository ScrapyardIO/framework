<?php

namespace Fabricate\Cache;

use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

class CacheServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->container->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->container->singleton(CacheManager::class, function ($app) {
            return $app->make('cache');
        });

        $this->container->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });

        $this->container->singleton(RateLimiter::class, function ($app) {
            return new RateLimiter($app->make('cache')->driver(
                $app['config']->get('cache.limiter')
            ));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'cache', 'cache.store', CacheManager::class, RateLimiter::class,
        ];
    }
}
