<?php

use Fabricate\Concurrency\ConcurrencyManager;
use Fabricate\Concurrency\SyncDriver;
use Fabricate\Process\Factory;

use function Fabricate\NutsAndBolts\Helpers\workshop_binary;

test('process factory runs a command synchronously', function () {
    $factory = new Factory;

    $result = $factory->run('echo hello-process');

    expect($result->successful())->toBeTrue()
        ->and($result->seeInOutput('hello-process'))->toBeTrue();
});

test('process factory can fake processes', function () {
    $factory = new Factory;
    $factory->fake([
        'echo *' => fn () => $factory->result('faked'),
    ]);

    $result = $factory->run('echo anything');

    expect($result->output())->toContain('faked');
    $factory->assertRan(fn ($process) => str_contains($process->command, 'echo'));
});

test('sync concurrency driver runs tasks in order', function () {
    $driver = new SyncDriver;

    $results = $driver->run([
        fn () => 1,
        fn () => 2,
    ]);

    expect($results)->toBe([1, 2]);
});

test('concurrency manager resolves sync driver', function () {
    $basePath = sys_get_temp_dir().'/scrapyard-io-concurrency-'.uniqid();
    mkdir($basePath.'/config', 0777, true);

    file_put_contents($basePath.'/config/concurrency.php', <<<'PHP'
<?php

return ['default' => 'sync'];
PHP);

    try {
        $app = new \Fabricate\Core\Machine($basePath);
        $app->bootstrapWith([
            \Fabricate\Core\Bootstrap\LoadConfiguration::class,
            \Fabricate\Core\Bootstrap\RegisterProviders::class,
            \Fabricate\Core\Bootstrap\BootProviders::class,
        ]);

        /** @var ConcurrencyManager $manager */
        $manager = $app->make('concurrency');

        expect($manager->driver('sync'))->toBeInstanceOf(SyncDriver::class)
            ->and($manager->run([fn () => 10, fn () => 20]))->toBe([10, 20]);
    } finally {
        destroyTempMachinePath($basePath);
    }
});

test('process concurrency driver can run serialized closures via workshop', function () {
    $basePath = sys_get_temp_dir().'/scrapyard-io-process-concurrency-'.uniqid();
    mkdir($basePath.'/config', 0777, true);
    mkdir($basePath.'/bootstrap', 0777, true);

    file_put_contents($basePath.'/config/concurrency.php', <<<'PHP'
<?php

return ['default' => 'process'];
PHP);

    file_put_contents($basePath.'/bootstrap/providers.php', <<<'PHP'
<?php

return [];
PHP);

    try {
        $app = new \Fabricate\Core\Machine($basePath);
        $app->bootstrapWith([
            \Fabricate\Core\Bootstrap\LoadConfiguration::class,
            \Fabricate\Core\Bootstrap\RegisterProviders::class,
            \Fabricate\Core\Bootstrap\BootProviders::class,
        ]);

        /** @var ConcurrencyManager $manager */
        $manager = $app->make('concurrency');

        $results = $manager->driver('process')->run([
            fn () => 3,
            fn () => 4,
        ]);

        expect($results)->toBe([3, 4]);
    } finally {
        destroyTempMachinePath($basePath);
    }
})->skip(fn () => ! is_executable(workshop_binary()), 'Workshop binary not available in this environment');

