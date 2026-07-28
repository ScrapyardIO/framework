<?php

namespace Fabricate\Redis;

use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\ServiceProvider;

class RedisServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->program->singleton('redis', function ($app) {
            $config = $app->bound('config')
                ? $app->make('config')->get('redis', [])
                : [];

            return new RedisManager(
                $app,
                Arr::pull($config, 'client', 'phpredis'),
                $config
            );
        });

        $this->program->bind('redis.connection', function ($app) {
            return $app['redis']->connection();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['redis', 'redis.connection'];
    }
}
