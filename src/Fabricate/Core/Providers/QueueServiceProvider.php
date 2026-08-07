<?php

namespace Fabricate\Core\Providers;

use Fabricate\Contracts\Debug\ExceptionHandler;
use Fabricate\Contracts\Events\Dispatcher as EventDispatcher;
use Fabricate\MagicAliases\MagicAlias;
use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Queue\Connectors\BackgroundConnector;
use Fabricate\Queue\Connectors\DatabaseConnector;
use Fabricate\Queue\Connectors\DeferredConnector;
use Fabricate\Queue\Connectors\FailoverConnector;
use Fabricate\Queue\Connectors\NullConnector;
use Fabricate\Queue\Connectors\RedisConnector;
use Fabricate\Queue\Connectors\SyncConnector;
use Fabricate\Queue\Failed\FileFailedJobProvider;
use Fabricate\Queue\Failed\NullFailedJobProvider;
use Fabricate\Queue\Listener;
use Fabricate\Queue\QueueManager;
use Fabricate\Queue\QueueRoutes;
use Fabricate\Queue\Worker;

/**
 * Binds QueueManager, worker, listener, routes, and failed-job services.
 *
 * Public connectors: null, sync, deferred, background, failover, redis, database.
 * Failed drivers: null, file. SQS / Beanstalkd / Dynamo are not registered (no AWS).
 *
 * Core owns this glue — not fabricate/queue.
 */
class QueueServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerManager();
        $this->registerConnection();
        $this->registerWorker();
        $this->registerListener();
        $this->registerRoutes();
        $this->registerFailedJobServices();
    }

    /**
     * Register the queue manager.
     *
     * @return void
     */
    protected function registerManager(): void
    {
        $this->container->singleton('queue', function ($app) {
            return tap(new QueueManager($app), function ($manager) {
                $this->registerConnectors($manager);
            });
        });
    }

    /**
     * Register the default queue connection binding.
     *
     * @return void
     */
    protected function registerConnection(): void
    {
        $this->container->singleton('queue.connection', function ($app) {
            return $app['queue']->connection();
        });
    }

    /**
     * Register the connectors on the queue manager.
     *
     * @param  \Fabricate\Queue\QueueManager  $manager
     * @return void
     */
    public function registerConnectors(QueueManager $manager): void
    {
        foreach (['Null', 'Sync', 'Deferred', 'Background', 'Failover', 'Redis', 'Database'] as $connector) {
            $this->{"register{$connector}Connector"}($manager);
        }
    }

    /**
     * Register the Null queue connector.
     *
     * @param  \Fabricate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerNullConnector(QueueManager $manager): void
    {
        $manager->addConnector('null', function () {
            return new NullConnector;
        });
    }

    /**
     * Register the Sync queue connector.
     *
     * @param  \Fabricate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerSyncConnector(QueueManager $manager): void
    {
        $manager->addConnector('sync', function () {
            return new SyncConnector;
        });
    }

    /**
     * Register the Deferred queue connector.
     *
     * @param  \Fabricate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerDeferredConnector(QueueManager $manager): void
    {
        $manager->addConnector('deferred', function () {
            return new DeferredConnector;
        });
    }

    /**
     * Register the Background queue connector.
     *
     * @param  \Fabricate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerBackgroundConnector(QueueManager $manager): void
    {
        $manager->addConnector('background', function () {
            return new BackgroundConnector;
        });
    }

    /**
     * Register the Failover queue connector.
     *
     * @param  \Fabricate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerFailoverConnector(QueueManager $manager): void
    {
        $manager->addConnector('failover', function () use ($manager) {
            return new FailoverConnector(
                $manager,
                $this->container->make(EventDispatcher::class)
            );
        });
    }

    /**
     * Register the Redis queue connector.
     *
     * @param  \Fabricate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerRedisConnector(QueueManager $manager): void
    {
        $manager->addConnector('redis', function () {
            return new RedisConnector($this->container['redis']);
        });
    }

    /**
     * Register the Database queue connector.
     *
     * @param  \Fabricate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerDatabaseConnector(QueueManager $manager): void
    {
        $manager->addConnector('database', function () {
            return new DatabaseConnector($this->container['db']);
        });
    }

    /**
     * Register the queue worker.
     *
     * @return void
     */
    protected function registerWorker(): void
    {
        $this->container->singleton('queue.worker', function ($app) {
            $isDownForMaintenance = function () use ($app) {
                return method_exists($app, 'isDownForMaintenance')
                    ? $app->isDownForMaintenance()
                    : false;
            };

            $resetScope = function () use ($app) {
                if ($app->bound('log')) {
                    if (method_exists($app['log'], 'flushSharedContext')) {
                        $app['log']->flushSharedContext();
                    }

                    if (method_exists($app['log'], 'withoutContext')) {
                        $app['log']->withoutContext();
                    }
                }

                if ($app->bound('db') && method_exists($app['db'], 'getConnections')) {
                    foreach ($app['db']->getConnections() as $connection) {
                        $connection->resetTotalQueryDuration();
                        $connection->allowQueryDurationHandlersToRunAgain();
                    }
                }

                if (method_exists($app, 'forgetScopedInstances')) {
                    $app->forgetScopedInstances();
                }

                MagicAlias::clearResolvedInstances();

                memory_reset_peak_usage();
            };

            return new Worker(
                $app['queue'],
                $app['events'],
                $app[ExceptionHandler::class],
                $isDownForMaintenance,
                $resetScope
            );
        });
    }

    /**
     * Register the queue listener.
     *
     * @return void
     */
    protected function registerListener(): void
    {
        $this->container->singleton('queue.listener', function ($app) {
            return new Listener($app->basePath());
        });
    }

    /**
     * Register the default queue routes binding.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        $this->container->singleton('queue.routes', function () {
            return new QueueRoutes;
        });
    }

    /**
     * Register the failed job services.
     *
     * @return void
     */
    protected function registerFailedJobServices(): void
    {
        $this->container->singleton('queue.failer', function ($app) {
            $config = $app['config']['queue.failed'] ?? [];

            if (array_key_exists('driver', $config) &&
                (is_null($config['driver']) || $config['driver'] === 'null')) {
                return new NullFailedJobProvider;
            }

            if (isset($config['driver']) && $config['driver'] === 'file') {
                $storagePath = method_exists($app, 'storagePath')
                    ? $app->storagePath('framework/cache/failed-jobs.json')
                    : 'storage/framework/cache/failed-jobs.json';

                return new FileFailedJobProvider(
                    $config['path'] ?? $storagePath,
                    $config['limit'] ?? 100,
                    fn () => $app['cache']->store('file'),
                );
            }

            return new NullFailedJobProvider;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'queue',
            'queue.connection',
            'queue.failer',
            'queue.listener',
            'queue.routes',
            'queue.worker',
        ];
    }
}
