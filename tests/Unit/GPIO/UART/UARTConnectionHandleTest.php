<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\UART\FakeUARTConnectionHandle;
use GPIO\Common\GPIOConnectionHandle;
use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleContract;
use GPIO\Contracts\UART\UARTConnectionHandle as UARTConnectionHandleContract;
use GPIO\UART\UARTConnectionHandle;

// UARTConnectionHandle is abstract and carries no behavior of its own - it
// exists purely to be typed against - so it's exercised here through
// FakeUARTConnectionHandle, the simplest possible concrete subclass.

test('it is abstract', function () {
    expect((new ReflectionClass(UARTConnectionHandle::class))->isAbstract())->toBeTrue();
});

test('a concrete subclass is instantiable and implements the expected contracts', function () {
    $handle = new FakeUARTConnectionHandle;

    expect($handle)->toBeInstanceOf(UARTConnectionHandle::class)
        ->and($handle)->toBeInstanceOf(UARTConnectionHandleContract::class)
        ->and($handle)->toBeInstanceOf(GPIOConnectionHandleContract::class)
        ->and($handle)->toBeInstanceOf(GPIOConnectionHandle::class);
});

test('two instances are distinct handles', function () {
    $first = new FakeUARTConnectionHandle;
    $second = new FakeUARTConnectionHandle;

    expect($first)->not->toBe($second);
});
