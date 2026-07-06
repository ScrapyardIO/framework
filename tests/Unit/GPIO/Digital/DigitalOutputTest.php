<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalOutputDriverAdapter;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalPinConnectionHandle;
use GPIO\Digital\DigitalPin;
use GPIO\Digital\Output\DigitalOutput;

test('it extends DigitalPin', function () {
    $handle = new FakeDigitalPinConnectionHandle;
    $driver = new FakeDigitalOutputDriverAdapter;

    $pin = new DigitalOutput(17, 'my-consumer', $handle, $driver, false);

    expect($pin)->toBeInstanceOf(DigitalPin::class);
});

test('constructing it with default_state true immediately writes the pin high', function () {
    $handle = new FakeDigitalPinConnectionHandle;
    $driver = new FakeDigitalOutputDriverAdapter;

    new DigitalOutput(17, 'my-consumer', $handle, $driver, true);

    expect($driver->writeCalls)->toBe([
        [17, true, $handle],
    ]);
});

test('constructing it with default_state false immediately writes the pin low', function () {
    $handle = new FakeDigitalPinConnectionHandle;
    $driver = new FakeDigitalOutputDriverAdapter;

    new DigitalOutput(17, 'my-consumer', $handle, $driver, false);

    expect($driver->writeCalls)->toBe([
        [17, false, $handle],
    ]);
});

test('high() writes true using this pin and handle', function () {
    $handle = new FakeDigitalPinConnectionHandle;
    $driver = new FakeDigitalOutputDriverAdapter;
    $pin = new DigitalOutput(17, 'my-consumer', $handle, $driver, false);

    $pin->high();

    expect($driver->writeCalls)->toBe([
        [17, false, $handle],
        [17, true, $handle],
    ]);
});

test('low() writes false using this pin and handle', function () {
    $handle = new FakeDigitalPinConnectionHandle;
    $driver = new FakeDigitalOutputDriverAdapter;
    $pin = new DigitalOutput(17, 'my-consumer', $handle, $driver, true);

    $pin->low();

    expect($driver->writeCalls)->toBe([
        [17, true, $handle],
        [17, false, $handle],
    ]);
});
