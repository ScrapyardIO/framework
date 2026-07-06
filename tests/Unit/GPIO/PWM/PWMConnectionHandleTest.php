<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\PWM\FakePWMConnectionHandle;
use GPIO\Common\GPIOConnectionHandle;
use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleContract;
use GPIO\Contracts\PWM\PWMConnectionHandle as PWMConnectionHandleContract;
use GPIO\PWM\PWMConnectionHandle;

// PWMConnectionHandle is abstract and carries no behavior of its own - it
// exists purely to be typed against - so it's exercised here through
// FakePWMConnectionHandle, the simplest possible concrete subclass.

test('it is abstract', function () {
    expect((new ReflectionClass(PWMConnectionHandle::class))->isAbstract())->toBeTrue();
});

test('a concrete subclass is instantiable and implements the expected contracts', function () {
    $handle = new FakePWMConnectionHandle;

    expect($handle)->toBeInstanceOf(PWMConnectionHandle::class)
        ->and($handle)->toBeInstanceOf(PWMConnectionHandleContract::class)
        ->and($handle)->toBeInstanceOf(GPIOConnectionHandleContract::class)
        ->and($handle)->toBeInstanceOf(GPIOConnectionHandle::class);
});

test('two instances are distinct handles', function () {
    $first = new FakePWMConnectionHandle;
    $second = new FakePWMConnectionHandle;

    expect($first)->not->toBe($second);
});
