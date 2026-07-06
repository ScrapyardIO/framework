<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalPinConnectionHandle;
use GPIO\Common\GPIOConnectionHandle;
use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleContract;
use GPIO\Contracts\Digital\DigitalPinConnectionHandle as DigitalPinConnectionHandleContract;
use GPIO\Digital\DigitalPinConnectionHandle;

// DigitalPinConnectionHandle is abstract - it exists purely to be typed
// against, with no behavior of its own - so it's exercised here through
// FakeDigitalPinConnectionHandle, the simplest possible concrete subclass.

test('it is abstract', function () {
    expect((new ReflectionClass(DigitalPinConnectionHandle::class))->isAbstract())->toBeTrue();
});

test('a concrete subclass is instantiable and implements the expected contracts', function () {
    $handle = new FakeDigitalPinConnectionHandle;

    expect($handle)->toBeInstanceOf(DigitalPinConnectionHandle::class)
        ->and($handle)->toBeInstanceOf(DigitalPinConnectionHandleContract::class)
        ->and($handle)->toBeInstanceOf(GPIOConnectionHandleContract::class)
        ->and($handle)->toBeInstanceOf(GPIOConnectionHandle::class);
});

test('two instances are distinct handles', function () {
    $first = new FakeDigitalPinConnectionHandle;
    $second = new FakeDigitalPinConnectionHandle;

    expect($first)->not->toBe($second);
});
