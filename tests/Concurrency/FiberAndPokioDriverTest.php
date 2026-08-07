<?php

use Fabricate\Concurrency\ConcurrencyManager;
use Fabricate\Concurrency\FiberDriver;
use Fabricate\Concurrency\PokioDriver;
use Fabricate\Core\Machine;

use function Fabricate\Concurrency\Fiber\suspend;

test('fiber driver runs tasks and preserves keys', function () {
    $driver = new FiberDriver;

    $results = $driver->run([
        'a' => fn () => 1,
        'b' => fn () => 2,
    ]);

    expect($results)->toBe(['a' => 1, 'b' => 2]);
});

test('fiber driver interleaves suspendable tasks', function () {
    $driver = new FiberDriver;
    $order = [];

    $results = $driver->run([
        function () use (&$order) {
            $order[] = 'a1';
            suspend();
            $order[] = 'a2';

            return 'A';
        },
        function () use (&$order) {
            $order[] = 'b1';
            suspend();
            $order[] = 'b2';

            return 'B';
        },
    ]);

    expect($results)->toBe(['A', 'B'])
        ->and($order)->toBe(['a1', 'b1', 'a2', 'b2']);
});

test('concurrency manager resolves fiber driver', function () {
    $basePath = sys_get_temp_dir().'/scrapyard-io-fiber-concurrency-'.uniqid();
    mkdir($basePath.'/config', 0777, true);

    file_put_contents($basePath.'/config/concurrency.php', <<<'PHP'
<?php

return ['default' => 'fiber'];
PHP);

    try {
        $app = new Machine($basePath);
        $app->bootstrapWith([
            \Fabricate\Core\Bootstrap\LoadConfiguration::class,
            \Fabricate\Core\Bootstrap\RegisterProviders::class,
            \Fabricate\Core\Bootstrap\BootProviders::class,
        ]);

        /** @var ConcurrencyManager $manager */
        $manager = $app->make('concurrency');

        expect($manager->driver('fiber'))->toBeInstanceOf(FiberDriver::class)
            ->and($manager->driver('fiber')->run([fn () => 7]))->toBe([7]);
    } finally {
        destroyTempMachinePath($basePath);
    }
});

test('pokio driver throws when package is missing', function () {
    if (function_exists('async') && function_exists('await')) {
        $this->markTestSkipped('nunomaduro/pokio is installed in this environment.');
    }

    expect(fn () => new PokioDriver)
        ->toThrow(RuntimeException::class, 'nunomaduro/pokio');
});

test('pokio driver runs when package is available', function () {
    if (! function_exists('async') || ! function_exists('await')) {
        $this->markTestSkipped('nunomaduro/pokio is not installed.');
    }

    $driver = new PokioDriver;

    expect($driver->run([
        fn () => 1 + 1,
        fn () => 2 + 2,
    ]))->toBe([2, 4]);
});
