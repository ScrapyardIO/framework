<?php

namespace Fabricate\Bus;

use Fabricate\Contracts\Bus\Dispatcher as DispatcherContract;
use Fabricate\Contracts\Bus\QueueingDispatcher as QueueingDispatcherContract;
use Fabricate\Contracts\Queue\Factory as QueueFactoryContract;
use Fabricate\Contracts\Support\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

class BusServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->machine->singleton(Dispatcher::class, function ($app) {
            return new Dispatcher($app, function ($connection = null) use ($app) {
                return $app->make(QueueFactoryContract::class)->connection($connection);
            });
        });

        $this->machine->alias(Dispatcher::class, DispatcherContract::class);
        $this->machine->alias(DispatcherContract::class, QueueingDispatcherContract::class);
    }

    public function provides(): array
    {
        return [
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
