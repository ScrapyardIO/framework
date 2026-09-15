<?php

use GeneralPurposeIO\Circuits\CircuitRegistry;
use GeneralPurposeIO\Contracts\Circuits\CircuitException;
use ScrapyardIO\Tests\Support\Fixtures\CircuitRegistryDemoIc;

it('registers implementors only', function () {
    $registry = new CircuitRegistry;
    $registry->addCircuit('demo', CircuitRegistryDemoIc::class);
    $registry->addCircuit('nope', stdClass::class);

    expect($registry->listCircuits())->toBe(['demo' => CircuitRegistryDemoIc::class]);
});

it('build invokes the named protocol factory', function () {
    $registry = new CircuitRegistry;
    $registry->addCircuit('demo', CircuitRegistryDemoIc::class);

    $ic = $registry->build('demo', 'i2c', [
        'device' => 1,
        'adapter' => 'posix',
        'slave' => 0x3C,
        'boot_now' => false,
    ]);

    expect($ic)->toBeInstanceOf(CircuitRegistryDemoIc::class)
        ->and($ic->via)->toBe('i2c')
        ->and($ic->args['device'])->toBe(1)
        ->and($ic->args['adapter'])->toBe('posix')
        ->and($ic->args['slave'])->toBe(0x3C)
        ->and($ic->args['boot_now'])->toBeFalse();
});

it('fluent make maps spi driver and device prefixes', function () {
    $registry = new CircuitRegistry;
    $registry->addCircuit('demo', CircuitRegistryDemoIc::class);

    $ic = $registry->ic('demo')
        ->protocol('spi')
        ->driver('usb')
        ->device('ft232h')
        ->chipSelect(0)
        ->dc(1)
        ->rst(2)
        ->make();

    expect($ic)->toBeInstanceOf(CircuitRegistryDemoIc::class)
        ->and($ic->via)->toBe('spi')
        ->and($ic->args['spi_adapter'])->toBe('usb')
        ->and($ic->args['digital_adapter'])->toBe('usb')
        ->and($ic->args['spi_device'])->toBe('ft232h')
        ->and($ic->args['digital_device'])->toBe('ft232h')
        ->and($ic->args['chip_select'])->toBe(0)
        ->and($ic->args['dc_pin'])->toBe(1)
        ->and($ic->args['rst_pin'])->toBe(2);
});

it('build rejects missing required params', function () {
    $registry = new CircuitRegistry;
    $registry->addCircuit('demo', CircuitRegistryDemoIc::class);

    expect(fn () => $registry->build('demo', 'i2c', []))
        ->toThrow(CircuitException::class, 'missing required parameter [device]');
});
