<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalInputDriverAdapter;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalPinConnectionHandle;
use GPIO\Common\GPIOTransport;
use GPIO\Contracts\Digital\DigitalPinTransport;
use GPIO\Digital\DigitalPin;
use GPIO\Digital\Input\DigitalInput;

// DigitalPin is abstract. Its shared behaviour (constructor assignment,
// isLow()/isHigh()/close()) is exercised here through DigitalInput, the
// simplest concrete subclass with no constructor side effects. Everything
// asserted in this file is inherited, unchanged, by every other digital pin.

// pin/name are private on DigitalPin and handle/driver are protected on its
// parent GPIOTransport (there are no public accessors), so reflection is
// needed to assert the constructor actually assigned them.
function digitalPinProperty(string $class, string $property): ReflectionProperty
{
    $reflected = new ReflectionProperty($class, $property);
    $reflected->setAccessible(true);

    return $reflected;
}

test('the constructor assigns pin, name, handle and driver', function () {
    $handle = new FakeDigitalPinConnectionHandle;
    $driver = new FakeDigitalInputDriverAdapter;

    $pin = new DigitalInput(17, 'my-consumer', $handle, $driver, 1000, false, false);

    expect(digitalPinProperty(DigitalPin::class, 'pin')->getValue($pin))->toBe(17)
        ->and(digitalPinProperty(DigitalPin::class, 'name')->getValue($pin))->toBe('my-consumer')
        ->and(digitalPinProperty(GPIOTransport::class, 'handle')->getValue($pin))->toBe($handle)
        ->and(digitalPinProperty(GPIOTransport::class, 'driver')->getValue($pin))->toBe($driver)
        ->and($pin)->toBeInstanceOf(DigitalPinTransport::class);
});

test('isLow() and isHigh() are the negation of the driver read value', function () {
    $handle = new FakeDigitalPinConnectionHandle;
    $driver = new FakeDigitalInputDriverAdapter;
    $pin = new DigitalInput(17, 'my-consumer', $handle, $driver, 1000, false, false);

    $driver->readReturnValue = true;
    expect($pin->isHigh())->toBeTrue()
        ->and($pin->isLow())->toBeFalse();

    $driver->readReturnValue = false;
    expect($pin->isHigh())->toBeFalse()
        ->and($pin->isLow())->toBeTrue();
});

test('isLow() and isHigh() read from the driver using this pin and handle', function () {
    $handle = new FakeDigitalPinConnectionHandle;
    $driver = new FakeDigitalInputDriverAdapter;
    $pin = new DigitalInput(17, 'my-consumer', $handle, $driver, 1000, false, false);

    $pin->isHigh();
    $pin->isLow();

    expect($driver->readCalls)->toBe([
        [17, $handle],
        [17, $handle],
    ]);
});

test('close() delegates to the driver using this pin\'s own handle', function () {
    $handle = new FakeDigitalPinConnectionHandle;
    $unrelatedHandle = new FakeDigitalPinConnectionHandle;
    $driver = new FakeDigitalInputDriverAdapter;
    $pin = new DigitalInput(17, 'my-consumer', $handle, $driver, 1000, false, false);

    $pin->close($unrelatedHandle);

    expect($driver->closeCalls)->toBe([$handle]);
});
