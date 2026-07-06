<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalCarrierDriverManager;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalConnectionBus;
use GPIO\Common\GPIOCarriers;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\Digital\DigitalPinException;
use GPIO\Contracts\Digital\LineBias;
use GPIO\Digital\Input\DigitalInputConnectionFactory;
use ScrapyardIO\NutsAndBolts\Reflection;

beforeEach(function () {
    $property = (new ReflectionClass(GPIOCarriers::class))->getProperty('instance');
    $property->setAccessible(true);
    $property->setValue(null, null);

    GPIOCarriers::boot(['fake-digital' => FakeDigitalCarrierDriverManager::class]);
});

test('it is registered under the digital-in protocol', function () {
    $attribute = Reflection::reflect_class(DigitalInputConnectionFactory::class, CarrierFactory::class);

    expect($attribute)->not->toBeNull()
        ->and($attribute->newInstance()->protocol)->toBe('digital-in');
});

test('its defaults match a plain, unconfigured digital input', function () {
    $factory = new DigitalInputConnectionFactory('fake-digital');

    expect($factory->timeout_ms)->toBe(1000)
        ->and($factory->active_low)->toBeFalse()
        ->and($factory->rising_events)->toBeFalse()
        ->and($factory->falling_events)->toBeFalse()
        ->and($factory->line_bias)->toBe(LineBias::AS_IS);
});

test('activeLow(), lineBias(), withEvents() and timeout() are fluent and set the expected properties', function () {
    $factory = new DigitalInputConnectionFactory('fake-digital');

    $result = $factory
        ->activeLow()
        ->lineBias(LineBias::PULL_UP)
        ->withEvents(true, false)
        ->timeout(250);

    expect($result)->toBe($factory)
        ->and($factory->active_low)->toBeTrue()
        ->and($factory->line_bias)->toBe(LineBias::PULL_UP)
        ->and($factory->rising_events)->toBeTrue()
        ->and($factory->falling_events)->toBeFalse()
        ->and($factory->timeout_ms)->toBe(250);
});

test('create() throws when no device has been set', function () {
    (new DigitalInputConnectionFactory('fake-digital'))->pin(4)->create();
})->throws(DigitalPinException::class, 'DigitalPin device is missing.');

test('create() throws when no pin has been set', function () {
    (new DigitalInputConnectionFactory('fake-digital'))->device('gpiochip0')->create();
})->throws(DigitalPinException::class, 'DigitalPin offset is missing.');

test('create() forwards every configured option to the driver adapter', function () {
    $addlPin = (new DigitalInputConnectionFactory('fake-digital'))->pin(5);

    $factory = (new DigitalInputConnectionFactory('fake-digital'))
        ->device('gpiochip0')
        ->pin(17)
        ->name('my-consumer')
        ->timeout(500)
        ->withEvents(true, true)
        ->lineBias(LineBias::PULL_DOWN)
        ->activeLow();

    $result = $factory->createWith('gpiochip0', [$addlPin]);

    expect($result)->toBeInstanceOf(FakeDigitalConnectionBus::class)
        ->and($result->arguments)->toBe([
            'device' => 'gpiochip0',
            'pin' => 17,
            'consumer' => 'my-consumer',
            'addl_pins' => [$addlPin],
            'timeout' => 500,
            'rising_events' => true,
            'falling_events' => true,
            'line_bias' => LineBias::PULL_DOWN,
            'active_low' => true,
        ]);
});
