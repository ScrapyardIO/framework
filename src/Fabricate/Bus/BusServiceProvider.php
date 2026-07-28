<?php

namespace Fabricate\Bus;

use Fabricate\Contracts\Bus\Dispatcher as DispatcherContract;
use Fabricate\Contracts\Bus\QueueingDispatcher as QueueingDispatcherContract;
use Fabricate\Contracts\Queue\Factory as QueueFactoryContract;
use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

class BusServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->program->singleton('bus', function ($app) {
            return new Dispatcher($app, function ($connection = null) use ($app) {
                return $app->make(QueueFactoryContract::class)->connection($connection);
            });
        });

        $this->program->singleton(Dispatcher::class, function ($app) {
            return $app->make('bus');
        });

        $this->program->alias(Dispatcher::class, DispatcherContract::class);
        $this->program->alias(DispatcherContract::class, QueueingDispatcherContract::class);
    }

    public function provides(): array
    {
        return [
            'bus',
            Dispatcher::class,
            DispatcherContract::class,
            QueueingDispatcherContract::class,
        ];
    }

    /*
     * Deferred scaffolding for queue persistence / batching (intentionally disabled):
     *
     * protected function registerBatchServices(): void
     * {
     *     // BatchRepository binding
     *     // DatabaseBatchRepository binding
     *     // DynamoBatchRepository binding
     * }
     */
}
