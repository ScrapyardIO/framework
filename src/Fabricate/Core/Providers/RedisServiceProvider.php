<?php

namespace Fabricate\Core\Providers;

use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Redis\RedisManager;

/**
 * Binds RedisManager as `redis` / `redis.connection`.
 *
 * Core owns this glue — not fabricate/redis.
 */
class RedisServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->container->singleton('redis', function ($app) {
            $config = $app->bound('config')
                ? $app->make('config')->get('redis', [])
                : [];

            return new RedisManager(
                $app,
                Arr::pull($config, 'client', 'phpredis'),
                $config
            );
        });

        $this->container->bind('redis.connection', function ($app) {
            return $app['redis']->connection();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['redis', 'redis.connection'];
    }
}
