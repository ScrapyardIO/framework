<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalCarrierDriverManager;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalConnectionBus;
use GPIO\Common\GPIOCarriers;
use GPIO\Digital\Output\DigitalOutputConnectionFactory;

// DigitalPinConnectionFactory is abstract, so its shared behavior (pin(),
// name(), device(), createWith()) is exercised here through the simplest
// concrete subclass, DigitalOutputConnectionFactory. Everything asserted in
// this file is inherited, unchanged, by every other digital pin factory.

beforeEach(function () {
    $property = (new ReflectionClass(GPIOCarriers::class))->getProperty('instance');
    $property->setAccessible(true);
    $property->setValue(null, null);

    GPIOCarriers::boot(['fake-digital' => FakeDigitalCarrierDriverManager::class]);
});

test('pin(), name() and device() are fluent and set the expected properties', function () {
    $factory = new DigitalOutputConnectionFactory('fake-digital');

    $result = $factory->pin(17)->name('my-consumer')->device('gpiochip0');

    expect($result)->toBe($factory)
        ->and($factory->pin)->toBe(17)
        ->and($factory->name)->toBe('my-consumer')
        ->and($factory->gpio_chip)->toBe('gpiochip0');
});

test('name defaults to scrapyard-io-gpio and pin/device default to null', function () {
    $factory = new DigitalOutputConnectionFactory('fake-digital');

    expect($factory->name)->toBe('scrapyard-io-gpio')
        ->and($factory->pin)->toBeNull()
        ->and($factory->gpio_chip)->toBeNull()
        ->and($factory->addl_pins)->toBe([]);
});

test('createWith() sets the device and additional pins, then delegates to create()', function () {
    $factory = (new DigitalOutputConnectionFactory('fake-digital'))->pin(4);

    $addlPins = [(new DigitalOutputConnectionFactory('fake-digital'))->pin(5)];
    $result = $factory->createWith('gpiochip1', $addlPins);

    expect($result)->toBeInstanceOf(FakeDigitalConnectionBus::class)
        ->and($factory->gpio_chip)->toBe('gpiochip1')
        ->and($factory->addl_pins)->toBe($addlPins)
        ->and($result->arguments['device'])->toBe('gpiochip1')
        ->and($result->arguments['addl_pins'])->toBe($addlPins);
});
