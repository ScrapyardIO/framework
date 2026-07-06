<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalCarrierDriverManager;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalConnectionBus;
use GPIO\Common\GPIOCarriers;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\Digital\DigitalPinException;
use GPIO\Digital\Output\DigitalOutputConnectionFactory;
use ScrapyardIO\NutsAndBolts\Reflection;

beforeEach(function () {
    $property = (new ReflectionClass(GPIOCarriers::class))->getProperty('instance');
    $property->setAccessible(true);
    $property->setValue(null, null);

    GPIOCarriers::boot(['fake-digital' => FakeDigitalCarrierDriverManager::class]);
});

test('it is registered under the digital-out protocol', function () {
    $attribute = Reflection::reflect_class(DigitalOutputConnectionFactory::class, CarrierFactory::class);

    expect($attribute)->not->toBeNull()
        ->and($attribute->newInstance()->protocol)->toBe('digital-out');
});

test('its default state is false', function () {
    expect((new DigitalOutputConnectionFactory('fake-digital'))->default_state)->toBeFalse();
});

test('defaultState() is fluent and sets the expected property', function () {
    $factory = new DigitalOutputConnectionFactory('fake-digital');

    $result = $factory->defaultState(true);

    expect($result)->toBe($factory)
        ->and($factory->default_state)->toBeTrue();
});

test('create() throws when no device has been set', function () {
    (new DigitalOutputConnectionFactory('fake-digital'))->pin(4)->create();
})->throws(DigitalPinException::class, 'DigitalPin device is missing.');

test('create() throws when no pin has been set', function () {
    (new DigitalOutputConnectionFactory('fake-digital'))->device('gpiochip0')->create();
})->throws(DigitalPinException::class, 'DigitalPin offset is missing.');

test('create() forwards every configured option to the driver adapter', function () {
    $addlPin = (new DigitalOutputConnectionFactory('fake-digital'))->pin(5);

    $factory = (new DigitalOutputConnectionFactory('fake-digital'))
        ->device('gpiochip0')
        ->pin(17)
        ->name('my-consumer')
        ->defaultState(true);

    $result = $factory->createWith('gpiochip0', [$addlPin]);

    expect($result)->toBeInstanceOf(FakeDigitalConnectionBus::class)
        ->and($result->arguments)->toBe([
            'device' => 'gpiochip0',
            'pin' => 17,
            'consumer' => 'my-consumer',
            'addl_pins' => [$addlPin],
            'default_state' => true,
        ]);
});
