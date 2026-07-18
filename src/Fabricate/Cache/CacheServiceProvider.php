<?php

namespace Fabricate\Cache;

use Fabricate\Contracts\Support\DeferrableProvider;
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
        $this->machine->singleton('cache', function ($app) {
            return new CacheManager($app);
        });

        $this->machine->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });

        $this->machine->singleton(RateLimiter::class, function ($app) {
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
            'cache', 'cache.store', RateLimiter::class,
        ];
    }
}
