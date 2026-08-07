<?php

require __DIR__.'/helpers.php';

use Fabricate\Contracts\Console\CLIKernel;
use Fabricate\Core\Console\AboutCommand;

afterEach(function () {
    AboutCommand::flushState();
});

test('about lists environment cache and drivers without hardware sections', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $kernel = $app->make(CLIKernel::class);

        $status = $kernel->call('about', ['--json' => true]);
        $json = json_decode($kernel->output(), true);

        expect($status)->toBe(0)
            ->and($json)->toHaveKeys(['environment', 'cache', 'drivers'])
            ->and($json)->not->toHaveKey('integrated_circuits')
            ->and($json)->not->toHaveKey('sensors')
            ->and($json)->not->toHaveKey('displays')
            ->and($json['environment']['application_name'] ?? null)->not->toBeNull()
            ->and($json['environment']['wrench_installed'])->toBeFalse()
            ->and($json['cache'])->toHaveKeys(['config', 'events']);
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('packages can override about rows via AboutCommand::add', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);

        AboutCommand::add('Environment', [
            'Wrench Installed' => AboutCommand::format(true),
        ]);

        $kernel = $app->make(CLIKernel::class);
        $status = $kernel->call('about', ['--json' => true]);
        $json = json_decode($kernel->output(), true);

        expect($status)->toBe(0)
            ->and($json['environment']['wrench_installed'])->toBeTrue();
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('packages can add about sections via AboutCommand::add', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);

        AboutCommand::add('Board Kit', [
            'Firmware' => '1.2.3',
            'Protocol' => 'i2c',
        ]);

        $kernel = $app->make(CLIKernel::class);
        $status = $kernel->call('about', ['--json' => true]);
        $json = json_decode($kernel->output(), true);

        expect($status)->toBe(0)
            ->and($json)->toHaveKey('board_kit')
            ->and($json['board_kit']['firmware'])->toBe('1.2.3')
            ->and($json['board_kit']['protocol'])->toBe('i2c');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('about --only filters sections', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $kernel = $app->make(CLIKernel::class);

        $status = $kernel->call('about', ['--json' => true, '--only' => 'environment']);
        $json = json_decode($kernel->output(), true);

        expect($status)->toBe(0)
            ->and($json)->toHaveKey('environment')
            ->and($json)->not->toHaveKey('drivers')
            ->and($json)->not->toHaveKey('cache');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});
