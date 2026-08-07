<?php

require __DIR__.'/helpers.php';

use Fabricate\Contracts\Console\CLIKernel;
use Symfony\Component\Console\Output\BufferedOutput;

test('event:cache writes a cache file and event:clear removes it', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('event:cache', [], $output);
        $cached = $app->getCachedEventsPath();

        expect($status)->toBe(0)
            ->and(file_exists($cached))->toBeTrue()
            ->and(require $cached)->toBeArray()
            ->and($output->fetch())->toContain('Events cached successfully.');

        $output = new BufferedOutput;
        $status = $app->make(CLIKernel::class)->call('event:clear', [], $output);

        expect($status)->toBe(0)
            ->and(file_exists($cached))->toBeFalse()
            ->and($output->fetch())->toContain('Cached events cleared successfully.');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('make:listener creates a typed listener class', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('make:listener', [
            'name' => 'SendProbeTelemetry',
            '--event' => 'BoardProbed',
            '--no-interaction' => true,
        ], $output);

        $expectedPath = $basePath.'/app/Listeners/SendProbeTelemetry.php';

        expect($status)->toBe(0)
            ->and(file_exists($expectedPath))->toBeTrue()
            ->and(file_get_contents($expectedPath))->toContain('namespace App\\Listeners;')
            ->and(file_get_contents($expectedPath))->toContain('use App\\Events\\BoardProbed;')
            ->and(file_get_contents($expectedPath))->toContain('function handle(BoardProbed $event): void');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('event:list lists the framework listeners registered on a fresh application', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('event:list', [], $output);

        expect($status)->toBe(0)
            ->and($output->fetch())->toContain('Fabricate\Console\Events\CommandFinished');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('event:list --event filters by event name', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('event:list', [
            '--event' => 'DoesNotExistAnywhere',
        ], $output);

        expect($status)->toBe(0)
            ->and($output->fetch())->toContain("doesn't have any events");
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});
