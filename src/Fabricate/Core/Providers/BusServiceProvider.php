<?php

namespace Fabricate\Core\Providers;

use Fabricate\Bus\Dispatcher;
use Fabricate\Contracts\Bus\Dispatcher as DispatcherContract;
use Fabricate\Contracts\Bus\QueueingDispatcher as QueueingDispatcherContract;
use Fabricate\Contracts\Queue\Factory as QueueFactoryContract;
use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

/**
 * Binds the Bus Dispatcher as `bus`.
 *
 * Core owns this glue — not fabricate/bus.
 */
class BusServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->container->singleton('bus', function ($app) {
            return new Dispatcher($app, function ($connection = null) use ($app) {
                return $app->make(QueueFactoryContract::class)->connection($connection);
            });
        });

        $this->container->singleton(Dispatcher::class, function ($app) {
            return $app->make('bus');
        });

        $this->container->alias(Dispatcher::class, DispatcherContract::class);
        $this->container->alias(DispatcherContract::class, QueueingDispatcherContract::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'bus',
            Dispatcher::class,
            DispatcherContract::class,
            QueueingDispatcherContract::class,
        ];
    }
}
