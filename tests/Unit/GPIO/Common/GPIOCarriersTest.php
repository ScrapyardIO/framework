<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\FakeCarrierDriverManager;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\FakeGPIODriverAdapter;
use GPIO\Common\GPIOCarriers;
use GPIO\Common\LoadDefaultProtocolManagers;
use GPIO\Contracts\Common\GPIOException;

function resetGpioCarriersSingleton(): void
{
    $property = (new ReflectionClass(GPIOCarriers::class))->getProperty('instance');
    $property->setAccessible(true);
    $property->setValue(null, null);
}

function gpioCarriersLibraries(GPIOCarriers $instance): array
{
    $property = (new ReflectionClass(GPIOCarriers::class))->getProperty('carrier_libraries');
    $property->setAccessible(true);

    return $property->getValue($instance);
}

beforeEach(fn () => resetGpioCarriersSingleton());
afterEach(fn () => resetGpioCarriersSingleton());

test('boot() registers exactly the given protocols', function () {
    $instance = GPIOCarriers::boot(['fake' => FakeCarrierDriverManager::class]);

    expect(gpioCarriersLibraries($instance))->toBe(['fake' => FakeCarrierDriverManager::class]);
});

test('boot() is a singleton; a later call does not override the first', function () {
    GPIOCarriers::boot(['fake' => FakeCarrierDriverManager::class]);
    $second = GPIOCarriers::boot(['other' => FakeCarrierDriverManager::class]);

    expect(gpioCarriersLibraries($second))->toBe(['fake' => FakeCarrierDriverManager::class]);
});

test('boot() with no protocols falls back to LoadDefaultProtocolManagers::run()', function () {
    // LoadDefaultProtocolManagers reaches into the sibling microscrap/
    // package; see LoadDefaultProtocolManagersTest for why the real
    // application autoloader needs to be loaded to resolve it here.
    require_once dirname(__DIR__, 6).'/vendor/autoload.php';

    $expected = LoadDefaultProtocolManagers::run();
    expect($expected)->not->toBeEmpty();

    $instance = GPIOCarriers::boot();

    expect(gpioCarriersLibraries($instance))->toBe($expected);
});

test('__callStatic dispatches the driver argument to the registered manager', function () {
    GPIOCarriers::boot(['fake' => FakeCarrierDriverManager::class]);

    expect(GPIOCarriers::fake('digital-in'))->toBeInstanceOf(FakeGPIODriverAdapter::class);
});

test('__callStatic lets the manager\'s own exceptions propagate', function () {
    GPIOCarriers::boot(['fake' => FakeCarrierDriverManager::class]);

    GPIOCarriers::fake('pwm');
})->throws(GPIOException::class, 'fake does not support pwm.');

test('__callStatic throws for a carrier that was never registered', function () {
    GPIOCarriers::boot(['fake' => FakeCarrierDriverManager::class]);

    GPIOCarriers::usb('digital-in');
})->throws(GPIOException::class, 'GPIO\Common\GPIOCarriers does not implement a static usb method.');
