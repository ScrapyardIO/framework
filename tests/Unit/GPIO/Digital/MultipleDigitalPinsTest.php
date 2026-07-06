<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalInputDriverAdapter;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalPinConnectionHandle;
use GPIO\Common\GPIOConnectionBus;
use GPIO\Contracts\Digital\DigitalPinBus;
use GPIO\Digital\Input\DigitalInput;
use GPIO\Digital\MultipleDigitalPins;

test('it is a GPIOConnectionBus and a DigitalPinBus', function () {
    $bus = new MultipleDigitalPins([]);

    expect($bus)->toBeInstanceOf(GPIOConnectionBus::class)
        ->and($bus)->toBeInstanceOf(DigitalPinBus::class);
});

test('it stores the given pins as a public readonly property', function () {
    $pins = [
        'clock' => new DigitalInput(17, 'clock', new FakeDigitalPinConnectionHandle, new FakeDigitalInputDriverAdapter, 1000, false, false),
    ];

    $bus = new MultipleDigitalPins($pins);

    expect($bus->pins)->toBe($pins);
});

test('getPin() returns the pin registered under the given name', function () {
    $clock = new DigitalInput(17, 'clock', new FakeDigitalPinConnectionHandle, new FakeDigitalInputDriverAdapter, 1000, false, false);
    $data = new DigitalInput(27, 'data', new FakeDigitalPinConnectionHandle, new FakeDigitalInputDriverAdapter, 1000, false, false);

    $bus = new MultipleDigitalPins(['clock' => $clock, 'data' => $data]);

    expect($bus->getPin('clock'))->toBe($clock)
        ->and($bus->getPin('data'))->toBe($data);
});

test('getPin() returns null when no pin is registered under the given name', function () {
    $bus = new MultipleDigitalPins([]);

    expect($bus->getPin('missing'))->toBeNull();
});
