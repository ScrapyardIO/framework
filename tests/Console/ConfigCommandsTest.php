<?php

require __DIR__.'/helpers.php';

use Fabricate\Contracts\Console\CLIKernel;
use Symfony\Component\Console\Output\BufferedOutput;

test('env command displays the current environment', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('env', [], $output);

        expect($status)->toBe(0)
            ->and($output->fetch())->toContain('The application environment is [testing].');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('config show command renders a configuration key', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('config:show', ['config' => 'machine.name'], $output);

        expect($status)->toBe(0)
            ->and($output->fetch())->toContain('ScrapyardIO');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('key generate show option prints a base64 key', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('key:generate', ['--show' => true], $output);

        expect($status)->toBe(0)
            ->and($output->fetch())->toMatch('/base64:[A-Za-z0-9+\/=]+/');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('config clear removes the configuration cache file', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $cached = $app->getCachedConfigPath();
        file_put_contents($cached, '<?php return [];'.PHP_EOL);

        $output = new BufferedOutput;
        $status = $app->make(CLIKernel::class)->call('config:clear', [], $output);

        expect($status)->toBe(0)
            ->and(file_exists($cached))->toBeFalse()
            ->and($output->fetch())->toContain('Configuration cache cleared successfully.');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('config cache writes a serializable configuration cache file', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('config:cache', [], $output);
        $cached = $app->getCachedConfigPath();

        expect($status)->toBe(0)
            ->and(file_exists($cached))->toBeTrue()
            ->and(require $cached)->toBeArray()
            ->and($output->fetch())->toContain('Configuration cached successfully.');
    } finally {
        // Nested Machine from config:cache may stack handlers; unwind a few levels only.
        for ($i = 0; $i < 5; $i++) {
            restore_exception_handler();
            restore_error_handler();
        }
        destroyConsoleTestBasePath($basePath);
    }
});
