<?php

require __DIR__.'/helpers.php';

use Fabricate\Contracts\Console\CLIKernel;
use Symfony\Component\Console\Output\BufferedOutput;

test('cache clear flushes the default store', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $app['config']->set('cache.default', 'array');
        $app['cache']->put('probe', 'yes', 60);

        $output = new BufferedOutput;
        $status = $app->make(CLIKernel::class)->call('cache:clear', [], $output);

        expect($status)->toBe(0)
            ->and($app['cache']->get('probe'))->toBeNull()
            ->and($output->fetch())->toContain('Application cache cleared successfully.');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});

test('cache forget removes a single key', function () {
    $basePath = createConsoleTestBasePath();

    try {
        $app = bootstrapConsoleMachine($basePath);
        $app['config']->set('cache.default', 'array');
        $app['cache']->put('keep', 1, 60);
        $app['cache']->put('drop', 1, 60);

        $output = new BufferedOutput;
        $status = $app->make(CLIKernel::class)->call('cache:forget', ['key' => 'drop'], $output);

        expect($status)->toBe(0)
            ->and($app['cache']->has('drop'))->toBeFalse()
            ->and($app['cache']->get('keep'))->toBe(1)
            ->and($output->fetch())->toContain('removed from the cache');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});
