<?php

require __DIR__.'/../Console/helpers.php';

use Fabricate\Bus\Dispatcher;
use Fabricate\Contracts\Bus\Dispatcher as DispatcherContract;
use Fabricate\Contracts\Bus\QueueingDispatcher as QueueingDispatcherContract;
use Fabricate\Core\MagicAliases\Bus as BusAlias;

test('bus binding resolves dispatcher contracts', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);

        expect($app->bound('bus'))->toBeTrue()
            ->and($app->make('bus'))->toBeInstanceOf(Dispatcher::class)
            ->and($app->make(DispatcherContract::class))->toBeInstanceOf(Dispatcher::class)
            ->and($app->make(QueueingDispatcherContract::class))->toBeInstanceOf(Dispatcher::class);
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('bus dispatches sync commands through the container', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);

        $command = new class
        {
            public function handle(): string
            {
                return 'handled';
            }
        };

        expect($app->make('bus')->dispatch($command))->toBe('handled')
            ->and(BusAlias::dispatchSync($command))->toBe('handled');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('bus magic alias resolves through the container', function () {
    $basePath = createConsoleTestBasePath();

    try {
        bootstrapConsoleMachine($basePath);

        expect(BusAlias::getMagicAliasRoot())->toBeInstanceOf(Dispatcher::class);
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});
