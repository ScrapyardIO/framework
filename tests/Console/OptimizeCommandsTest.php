<?php

require __DIR__.'/helpers.php';

use Fabricate\Contracts\Console\CLIKernel;
use Fabricate\NutsAndBolts\ServiceProvider;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function () {
    ServiceProvider::$optimizeCommands = [];
    ServiceProvider::$optimizeClearCommands = [];
});

afterEach(function () {
    ServiceProvider::$optimizeCommands = [];
    ServiceProvider::$optimizeClearCommands = [];
});

test('optimize caches config and events', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('optimize', [], $output);

        expect($status)->toBe(0)
            ->and($output->fetch())->toContain('Caching framework bootstrap')
            ->and(is_file($app->getCachedConfigPath()))->toBeTrue()
            ->and(is_file($app->getCachedEventsPath()))->toBeTrue();
    } finally {
        unwindNestedMachineHandlers();
        destroyConsoleTestBasePath($basePath);
    }
});

test('optimize clear removes config events and flushes cache', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $kernel = $app->make(CLIKernel::class);

        $kernel->call('optimize');

        expect(is_file($app->getCachedConfigPath()))->toBeTrue();

        $output = new BufferedOutput;
        $status = $kernel->call('optimize:clear', [], $output);

        expect($status)->toBe(0)
            ->and($output->fetch())->toContain('Clearing cached bootstrap files')
            ->and(is_file($app->getCachedConfigPath()))->toBeFalse()
            ->and(is_file($app->getCachedEventsPath()))->toBeFalse();
    } finally {
        unwindNestedMachineHandlers();
        destroyConsoleTestBasePath($basePath);
    }
});

test('optimize except skips a task key', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('optimize', ['--except' => 'events'], $output);

        expect($status)->toBe(0)
            ->and(is_file($app->getCachedConfigPath()))->toBeTrue()
            ->and(is_file($app->getCachedEventsPath()))->toBeFalse();
    } finally {
        unwindNestedMachineHandlers();
        destroyConsoleTestBasePath($basePath);
    }
});

function unwindNestedMachineHandlers(): void
{
    for ($i = 0; $i < 5; $i++) {
        restore_exception_handler();
        restore_error_handler();
    }
}
