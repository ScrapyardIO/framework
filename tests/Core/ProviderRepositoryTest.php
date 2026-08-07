<?php

use Fabricate\Core\Machine;
use Fabricate\Core\ProviderRepository;
use Fabricate\Filesystem\Filesystem;

test('provider repository should recompile when manifest is missing or stale', function () {
    $basePath = sys_get_temp_dir().'/scrapyard-io-providers-'.uniqid();
    mkdir($basePath, 0777, true);

    try {
        $machine = new Machine($basePath);
        $files = new Filesystem;
        $repository = new ProviderRepository($machine, $files, $basePath.'/cache/services.php');

        expect($repository->shouldRecompile(null, []))->toBeTrue()
            ->and($repository->shouldRecompile(['providers' => ['A']], ['A']))->toBeFalse()
            ->and($repository->shouldRecompile(['providers' => ['A']], ['B']))->toBeTrue();
    } finally {
        if (is_dir($basePath)) {
            rmdir($basePath);
        }
    }
});
