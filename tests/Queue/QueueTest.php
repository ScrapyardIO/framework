<?php

require __DIR__.'/../Console/helpers.php';

use Fabricate\Contracts\Queue\Factory as QueueFactoryContract;
use Fabricate\Contracts\Queue\Queue as QueueContract;
use Fabricate\Core\MagicAliases\Queue as QueueAlias;
use Fabricate\Queue\QueueManager;
use Fabricate\Queue\SyncQueue;

test('queue binding and default sync connection resolve', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);

        expect($app->bound('queue'))->toBeTrue()
            ->and($app->make('queue'))->toBeInstanceOf(QueueManager::class)
            ->and($app->make(QueueFactoryContract::class))->toBeInstanceOf(QueueManager::class)
            ->and($app->make('queue.connection'))->toBeInstanceOf(SyncQueue::class)
            ->and($app->make(QueueContract::class))->toBeInstanceOf(SyncQueue::class)
            ->and($app['config']->get('queue.default'))->toBe('sync');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('queue magic alias connection returns sync driver', function () {
    $basePath = createConsoleTestBasePath();

    try {
        bootstrapConsoleMachine($basePath);

        expect(QueueAlias::connection())->toBeInstanceOf(SyncQueue::class)
            ->and(QueueAlias::getDefaultDriver())->toBe('sync');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('queue manager exposes public connectors including database', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        /** @var QueueManager $manager */
        $manager = $app->make('queue');

        expect($manager->connection('sync'))->toBeInstanceOf(SyncQueue::class)
            ->and($manager->connection('null'))->toBeInstanceOf(QueueContract::class)
            ->and($manager->connection('deferred'))->toBeInstanceOf(QueueContract::class)
            ->and($manager->connection('database'))->toBeInstanceOf(QueueContract::class);

        expect(fn () => $manager->connection('sqs'))
            ->toThrow(InvalidArgumentException::class);
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});
