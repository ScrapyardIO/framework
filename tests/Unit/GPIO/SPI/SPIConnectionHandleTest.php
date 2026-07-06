<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\SPI\FakeSPIConnectionHandle;
use GPIO\Common\GPIOConnectionHandle;
use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleContract;
use GPIO\Contracts\SPI\SPIConnectionHandle as SPIConnectionHandleContract;
use GPIO\SPI\SPIConnectionHandle;

// SPIConnectionHandle is abstract and carries no behavior of its own - it
// exists purely to be typed against - so it's exercised here through
// FakeSPIConnectionHandle, the simplest possible concrete subclass.

test('it is abstract', function () {
    expect((new ReflectionClass(SPIConnectionHandle::class))->isAbstract())->toBeTrue();
});

test('a concrete subclass is instantiable and implements the expected contracts', function () {
    $handle = new FakeSPIConnectionHandle;

    expect($handle)->toBeInstanceOf(SPIConnectionHandle::class)
        ->and($handle)->toBeInstanceOf(SPIConnectionHandleContract::class)
        ->and($handle)->toBeInstanceOf(GPIOConnectionHandleContract::class)
        ->and($handle)->toBeInstanceOf(GPIOConnectionHandle::class);
});

test('two instances are distinct handles', function () {
    $first = new FakeSPIConnectionHandle;
    $second = new FakeSPIConnectionHandle;

    expect($first)->not->toBe($second);
});
