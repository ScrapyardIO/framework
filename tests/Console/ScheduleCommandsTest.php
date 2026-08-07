<?php

require __DIR__.'/helpers.php';

use Fabricate\Contracts\Console\CLIKernel;
use Symfony\Component\Console\Output\BufferedOutput;

test('schedule list command succeeds with an empty schedule', function () {
    $basePath = createConsoleTestBasePath();
    file_put_contents($basePath.'/.env', "APP_KEY=\nAPP_ENV=testing\nCACHE_STORE=array\n");

    try {
        $app = bootstrapConsoleMachine($basePath);
        $output = new BufferedOutput;

        $status = $app->make(CLIKernel::class)->call('schedule:list', [], $output);

        expect($status)->toBe(0)
            ->and($output->fetch())->toContain('No scheduled tasks have been defined.');
    } finally {
        destroyConsoleTestBasePath($basePath);
    }
});
