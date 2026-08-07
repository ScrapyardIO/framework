<?php

use Fabricate\Filesystem\Filesystem;

test('filesystem put get and exists work in temp directory', function () {
    $filesystem = new Filesystem;
    $path = sys_get_temp_dir().'/scrapyard-io-framework-'.uniqid().'.txt';

    try {
        expect($filesystem->put($path, 'hello'))->not->toBeFalse()
            ->and($filesystem->exists($path))->toBeTrue()
            ->and($filesystem->get($path))->toBe('hello');
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

test('filesystem missing reports absent paths', function () {
    $filesystem = new Filesystem;

    expect($filesystem->missing(sys_get_temp_dir().'/scrapyard-io-missing-'.uniqid()))->toBeTrue();
});
