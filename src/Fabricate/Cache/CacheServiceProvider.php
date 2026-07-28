<?php

namespace Fabricate\Cache;

use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;
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
        $this->program->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->program->singleton(CacheManager::class, function ($app) {
            return $app->make('cache');
        });

        $this->program->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });

        $this->program->singleton(RateLimiter::class, function ($app) {
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