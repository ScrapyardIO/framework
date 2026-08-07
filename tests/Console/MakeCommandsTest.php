<?php

require __DIR__.'/helpers.php';

use Fabricate\Contracts\Console\CLIKernel;
use Symfony\Component\Console\Output\BufferedOutput;

test('make:class creates a PHP file under app/', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('make:class', [
            'name' => 'Testing/DemoClass',
            '--no-interaction' => true,
        ], $output);

        $expectedPath = $basePath.'/app/Testing/DemoClass.php';

        expect($status)->toBe(0)
            ->and(file_exists($expectedPath))->toBeTrue()
            ->and(file_get_contents($expectedPath))->toContain('namespace App\\Testing;')
            ->and(file_get_contents($expectedPath))->toContain('class DemoClass')
            ->and($output->fetch())->toContain('Class [');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('make:node creates a Node under app/Workflows', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('make:node', [
            'name' => 'ProbeBoard',
            '--no-interaction' => true,
        ], $output);

        $expectedPath = $basePath.'/app/Workflows/ProbeBoard.php';

        expect($status)->toBe(0)
            ->and(file_exists($expectedPath))->toBeTrue()
            ->and(file_get_contents($expectedPath))->toContain('namespace App\\Workflows;')
            ->and(file_get_contents($expectedPath))->toContain('extends Node')
            ->and(file_get_contents($expectedPath))->toContain('function prep(')
            ->and(file_get_contents($expectedPath))->toContain('function exec(')
            ->and(file_get_contents($expectedPath))->toContain('function post(');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('make:node --async creates an AsyncNode under app/Workflows', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('make:node', [
            'name' => 'FetchRemote',
            '--async' => true,
            '--no-interaction' => true,
        ], $output);

        $expectedPath = $basePath.'/app/Workflows/FetchRemote.php';

        expect($status)->toBe(0)
            ->and(file_exists($expectedPath))->toBeTrue()
            ->and(file_get_contents($expectedPath))->toContain('namespace App\\Workflows;')
            ->and(file_get_contents($expectedPath))->toContain('extends AsyncNode')
            ->and(file_get_contents($expectedPath))->toContain('function prepAsync(')
            ->and(file_get_contents($expectedPath))->toContain('function execAsync(')
            ->and(file_get_contents($expectedPath))->toContain('function postAsync(');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('vendor:publish help succeeds', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('vendor:publish', [
            '--help' => true,
        ], $output);

        expect($status)->toBe(0)
            ->and($output->fetch())->toContain('vendor:publish');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});
