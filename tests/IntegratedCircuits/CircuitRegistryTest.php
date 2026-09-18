<?php

use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException;
use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitRegistry as RegistryContract;
use GeneralPurposeIO\Core\MagicAliases\Circuit;
use GeneralPurposeIO\IntegratedCircuits\CircuitRegistry;
use ScrapyardIO\Tests\Support\Fixtures\CatalogDemoIc;

/** The wiring an app keeps in config/circuits/demo.php. */
function demoWiring(): array
{
    return [
        'default_config' => 'spi',
        'configs' => [
            'spi' => [
                'driver' => 'usb',
                'device' => 'ft232h',
                'chip_select' => 0,
                'dc' => ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 1],
                'rst' => ['driver' => 'usb', 'device' => 'ft232h', 'pin' => 2],
            ],
            'i2c' => ['driver' => 'native', 'device' => 1, 'slave' => 0x3C],
            'left' => ['protocol' => 'i2c', 'driver' => 'native', 'device' => 1, 'slave' => 0x3D],
        ],
    ];
}

/** A booted application whose circuits tree is the given one, and a catalog that knows the demo chip. */
function catalog(array $circuits = []): CircuitRegistry
{
    bootedApplication()->make('config')->set('circuits', $circuits);

    $registry = new CircuitRegistry;
    $registry->addCircuit('demo', CatalogDemoIc::class);

    return $registry;
}

it('is the registry contract, and catalogs implementors only', function () {
    $registry = catalog();
    $registry->addCircuit('nope', stdClass::class);
    $registry->addCircuit('missing', 'No\\Such\\Class');

    expect($registry)->toBeInstanceOf(RegistryContract::class)
        ->and($registry->listCircuits())->toBe(['demo' => CatalogDemoIc::class])
        ->and($registry->has('demo'))->toBeTrue()
        ->and($registry->has('nope'))->toBeFalse();
});

it('conjures the chip from its default config, keys as named factory arguments', function () {
    $ic = catalog(['demo' => demoWiring()])->conjure('demo');

    expect($ic)->toBeInstanceOf(CatalogDemoIc::class)
        ->and($ic->via)->toBe('spi')
        ->and($ic->args['driver'])->toBe('usb')
        ->and($ic->args['device'])->toBe('ft232h')
        ->and($ic->args['dc'])->toBe(['driver' => 'usb', 'device' => 'ft232h', 'pin' => 1])
        ->and($ic->args['rst']['pin'])->toBe(2)
        ->and($ic->args['boot_now'])->toBeTrue();
});

it('conjures a named config, and a config may name its own protocol', function () {
    $registry = catalog(['demo' => demoWiring()]);

    expect($registry->conjure('demo', 'i2c')->args['slave'])->toBe(0x3C)
        ->and($registry->conjure('demo', 'left')->via)->toBe('i2c')
        ->and($registry->conjure('demo', 'left')->args['slave'])->toBe(0x3D);
});

it('build invokes the named protocol factory, dropping keys it does not take', function () {
    $ic = catalog()->build('demo', 'i2c', ['driver' => 'native', 'device' => 1, 'ignored' => true]);

    expect($ic->args)->toBe(['driver' => 'native', 'device' => 1, 'slave' => 0x38, 'boot_now' => true]);
});

it('names what went wrong', function () {
    // config() answers the newest application, so the empty tree goes first.
    expect(fn () => catalog()->conjure('demo'))->toThrow(CircuitException::class, 'has no config at circuits.demo');

    $registry = catalog(['demo' => ['configs' => ['spi' => []]]]);

    expect(fn () => $registry->conjure('ghost'))->toThrow(CircuitException::class, 'Circuit [ghost] is not registered.')
        ->and(fn () => $registry->conjure('demo'))->toThrow(CircuitException::class, 'names no default_config')
        ->and(fn () => $registry->conjure('demo', 'uart'))->toThrow(CircuitException::class, 'circuits.demo.configs has no [uart]')
        ->and(fn () => $registry->conjure('demo', 'spi'))->toThrow(CircuitException::class, 'missing required parameter [driver]')
        ->and(fn () => $registry->build('demo', 'nope', []))->toThrow(CircuitException::class, 'no static protocol factory [nope]')
        ->and(fn () => $registry->build('demo', 'uart', []))->toThrow(CircuitException::class, 'must be a public static method')
        ->and(fn () => $registry->build('demo', 'bogus', []))->toThrow(CircuitException::class, 'must return an IntegratedCircuit');
});

it('binds circuit on the container, behind the Circuit alias', function () {
    $app = bootedApplication();
    $app->make('config')->set('circuits', ['demo' => demoWiring()]);

    expect($app->make('circuit'))->toBeInstanceOf(CircuitRegistry::class)
        ->and($app->make(RegistryContract::class))->toBe($app->make('circuit'));

    Circuit::setMagicAliasApplication($app);
    Circuit::addCircuit('demo', CatalogDemoIc::class);

    expect(Circuit::conjure('demo', 'i2c')->via)->toBe('i2c');

    Circuit::clearResolvedInstances();
});
