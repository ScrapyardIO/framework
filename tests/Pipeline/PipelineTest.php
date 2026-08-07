<?php

require __DIR__.'/../Console/helpers.php';

use Fabricate\Contracts\Pipeline\Hub as HubContract;
use Fabricate\Core\MagicAliases\Pipeline as PipelineAlias;
use Fabricate\Pipeline\Hub;
use Fabricate\Pipeline\Pipeline;

test('pipeline send through thenReturn with callables', function () {
    $result = (new Pipeline)
        ->send(1)
        ->through([
            fn ($value, $next) => $next($value + 1),
            fn ($value, $next) => $next($value * 2),
        ])
        ->thenReturn();

    expect($result)->toBe(4);
});

test('pipeline pipe appends additional pipes', function () {
    $result = (new Pipeline)
        ->send('a')
        ->through([fn ($value, $next) => $next($value.'b')])
        ->pipe(fn ($value, $next) => $next($value.'c'))
        ->thenReturn();

    expect($result)->toBe('abc');
});

test('pipeline finally runs after then', function () {
    $saw = null;

    $result = (new Pipeline)
        ->send('x')
        ->through([])
        ->finally(function ($passable) use (&$saw) {
            $saw = $passable;
        })
        ->then(fn ($passable) => strtoupper($passable));

    expect($result)->toBe('X')
        ->and($saw)->toBe('x');
});

test('pipeline hub runs named pipelines', function () {
    $hub = new Hub;

    $hub->defaults(function (Pipeline $pipeline, $object) {
        return $pipeline->send($object)
            ->through([fn ($value, $next) => $next($value.'-default')])
            ->thenReturn();
    });

    $hub->pipeline('upper', function (Pipeline $pipeline, $object) {
        return $pipeline->send($object)
            ->through([fn ($value, $next) => $next(strtoupper($value))])
            ->thenReturn();
    });

    expect($hub->pipe('board'))->toBe('board-default')
        ->and($hub->pipe('pi', 'upper'))->toBe('PI');
});

test('pipeline binding and magic alias resolve through the container', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);

        expect($app->bound('pipeline'))->toBeTrue()
            ->and($app->make('pipeline'))->toBeInstanceOf(Pipeline::class)
            ->and($app->make(HubContract::class))->toBeInstanceOf(Hub::class)
            ->and(
                PipelineAlias::send(2)
                    ->through([fn ($value, $next) => $next($value + 3)])
                    ->thenReturn()
            )->toBe(5);
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});
