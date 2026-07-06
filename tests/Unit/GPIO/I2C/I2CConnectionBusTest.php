<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalInputDriverAdapter;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalPinConnectionHandle;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\I2C\FakeI2CConnectionHandle;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\I2C\FakeI2CDriverAdapter;
use GPIO\Common\GPIOConnectionBus;
use GPIO\Contracts\Digital\DigitalPinBus;
use GPIO\Digital\Input\DigitalInput;
use GPIO\Digital\MultipleDigitalPins;
use GPIO\I2C\I2C;
use GPIO\I2C\I2CConnectionBus;

test('it extends MultipleDigitalPins and is a GPIOConnectionBus and DigitalPinBus', function () {
    $bus = new I2CConnectionBus(new I2C(new FakeI2CConnectionHandle, new FakeI2CDriverAdapter));

    expect($bus)->toBeInstanceOf(MultipleDigitalPins::class)
        ->and($bus)->toBeInstanceOf(GPIOConnectionBus::class)
        ->and($bus)->toBeInstanceOf(DigitalPinBus::class);
});

test('it stores the given I2C transport as a public readonly property', function () {
    $i2c = new I2C(new FakeI2CConnectionHandle, new FakeI2CDriverAdapter);

    $bus = new I2CConnectionBus($i2c);

    expect($bus->i2c)->toBe($i2c);
});

test('it stores the given additional digital pins and getPin() finds them by name', function () {
    $i2c = new I2C(new FakeI2CConnectionHandle, new FakeI2CDriverAdapter);
    $resetPin = new DigitalInput(17, 'reset', new FakeDigitalPinConnectionHandle, new FakeDigitalInputDriverAdapter, 1000, false, false);

    $bus = new I2CConnectionBus($i2c, ['reset' => $resetPin]);

    expect($bus->pins)->toBe(['reset' => $resetPin])
        ->and($bus->getPin('reset'))->toBe($resetPin)
        ->and($bus->getPin('missing'))->toBeNull();
});

test('additional digital pins default to an empty array', function () {
    $bus = new I2CConnectionBus(new I2C(new FakeI2CConnectionHandle, new FakeI2CDriverAdapter));

    expect($bus->pins)->toBe([]);
});
