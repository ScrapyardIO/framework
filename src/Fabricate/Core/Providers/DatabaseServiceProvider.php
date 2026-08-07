<?php

namespace Fabricate\Core\Providers;

use Fabricate\Contracts\Database\ConcurrencyErrorDetector as ConcurrencyErrorDetectorContract;
use Fabricate\Contracts\Database\LostConnectionDetector as LostConnectionDetectorContract;
use Fabricate\Database\ConcurrencyErrorDetector;
use Fabricate\Database\ConnectionResolverInterface;
use Fabricate\Database\Connectors\ConnectionFactory;
use Fabricate\Database\DatabaseManager;
use Fabricate\Database\DatabaseTransactionsManager;
use Fabricate\Database\LostConnectionDetector;
use Fabricate\Database\Polisher\Model;
use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
        Model::setConnectionResolver($this->container['db']);
        if ($this->container->bound('events')) Model::setEventDispatcher($this->container['events']);
    }

    public function register(): void
    {
        Model::clearBootedModels();
        $this->container->singleton('db.factory', fn ($app) => new ConnectionFactory($app));
        $this->container->singleton('db', fn ($app) => new DatabaseManager($app, $app['db.factory']));
        $this->container->bind(ConnectionResolverInterface::class, fn ($app) => $app['db']);
        $this->container->bind('db.connection', fn ($app) => $app['db']->connection());
        $this->container->bind('db.schema', fn ($app) => $app['db']->connection()->getSchemaBuilder());
        $this->container->singleton('db.transactions', fn () => new DatabaseTransactionsManager);
        $this->container->singleton(ConcurrencyErrorDetectorContract::class, fn () => new ConcurrencyErrorDetector);
        $this->container->singleton(LostConnectionDetectorContract::class, fn () => new LostConnectionDetector);
    }

    public function provides(): array
    {
        return [
            'db',
            'db.connection',
            'db.factory',
            'db.schema',
            'db.transactions',
            ConnectionResolverInterface::class,
            ConcurrencyErrorDetectorContract::class,
            LostConnectionDetectorContract::class,
        ];
    }
}
