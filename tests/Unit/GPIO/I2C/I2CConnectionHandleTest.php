<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\I2C\FakeI2CConnectionHandle;
use GPIO\Common\GPIOConnectionHandle;
use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleContract;
use GPIO\Contracts\I2C\I2CConnectionHandle as I2CConnectionHandleContract;
use GPIO\I2C\I2CConnectionHandle;

// I2CConnectionHandle is abstract - it exists purely to be typed against -
// so it's exercised here through FakeI2CConnectionHandle, the simplest
// possible concrete subclass.

test('it is abstract', function () {
    expect((new ReflectionClass(I2CConnectionHandle::class))->isAbstract())->toBeTrue();
});

test('a concrete subclass is instantiable and implements the expected contracts', function () {
    $handle = new FakeI2CConnectionHandle(0x10);

    expect($handle)->toBeInstanceOf(I2CConnectionHandle::class)
        ->and($handle)->toBeInstanceOf(I2CConnectionHandleContract::class)
        ->and($handle)->toBeInstanceOf(GPIOConnectionHandleContract::class)
        ->and($handle)->toBeInstanceOf(GPIOConnectionHandle::class);
});

test('slaveAddress() returns the address it was constructed with', function () {
    $handle = new FakeI2CConnectionHandle(0x42);

    expect($handle->slaveAddress())->toBe(0x42);
});

test('slaveAddress() defaults to 0x10 when none is given', function () {
    $handle = new FakeI2CConnectionHandle;

    expect($handle->slaveAddress())->toBe(0x10);
});

test('two instances are distinct handles', function () {
    $first = new FakeI2CConnectionHandle;
    $second = new FakeI2CConnectionHandle;

    expect($first)->not->toBe($second);
});
