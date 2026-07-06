<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalInputDriverAdapter;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalPinConnectionHandle;
use GPIO\Digital\DigitalPin;
use GPIO\Digital\Input\DigitalInput;

test('it extends DigitalPin and stores its extra constructor arguments', function () {
    $handle = new FakeDigitalPinConnectionHandle;
    $driver = new FakeDigitalInputDriverAdapter;

    $pin = new DigitalInput(17, 'my-consumer', $handle, $driver, 500, true, false);

    $reflected = new ReflectionClass($pin);
    $timeoutMs = $reflected->getProperty('timeout_ms');
    $timeoutMs->setAccessible(true);
    $risingEvents = $reflected->getProperty('rising_events');
    $risingEvents->setAccessible(true);
    $fallingEvents = $reflected->getProperty('falling_events');
    $fallingEvents->setAccessible(true);

    expect($pin)->toBeInstanceOf(DigitalPin::class)
        ->and($timeoutMs->getValue($pin))->toBe(500)
        ->and($risingEvents->getValue($pin))->toBeTrue()
        ->and($fallingEvents->getValue($pin))->toBeFalse();
});

test('constructing it does not talk to the driver', function () {
    $handle = new FakeDigitalPinConnectionHandle;
    $driver = new FakeDigitalInputDriverAdapter;

    new DigitalInput(17, 'my-consumer', $handle, $driver, 500, true, false);

    expect($driver->readCalls)->toBe([])
        ->and($driver->listenCalls)->toBe([])
        ->and($driver->closeCalls)->toBe([]);
});
