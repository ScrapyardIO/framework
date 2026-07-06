<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\PWM\FakePWMCarrierDriverManager;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\PWM\FakePWMDriverAdapter;
use GPIO\Common\GPIO;
use GPIO\Common\GPIOCarriers;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\PWM\PWMChannelException;
use GPIO\PWM\MultiplePWMChannels;
use GPIO\PWM\PWMChannel;
use GPIO\PWM\PWMConnectionFactory;
use ScrapyardIO\NutsAndBolts\Reflection;

// driver_adapter is protected with no accessor, so reflection is needed to
// reach into the factory and assert what the underlying fake driver
// actually recorded.
function pwmFactoryDriverAdapter(PWMConnectionFactory $factory): FakePWMDriverAdapter
{
    $property = new ReflectionProperty($factory, 'driver_adapter');
    $property->setAccessible(true);

    return $property->getValue($factory);
}

function resetGpioAndCarriersSingletonsForPwm(): void
{
    foreach ([GPIO::class, GPIOCarriers::class] as $class) {
        $property = (new ReflectionClass($class))->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}

beforeEach(function () {
    resetGpioAndCarriersSingletonsForPwm();

    GPIOCarriers::boot(['fake-pwm' => FakePWMCarrierDriverManager::class]);
});

afterEach(function () {
    resetGpioAndCarriersSingletonsForPwm();
});

test('it is registered under the pwm protocol', function () {
    $attribute = Reflection::reflect_class(PWMConnectionFactory::class, CarrierFactory::class);

    expect($attribute)->not->toBeNull()
        ->and($attribute->newInstance()->protocol)->toBe('pwm');
});

test('its defaults match a plain, unconfigured PWM connection', function () {
    $factory = new PWMConnectionFactory('fake-pwm');

    expect($factory->channel)->toBeNull()
        ->and($factory->pwm_chip)->toBeNull()
        ->and($factory->name)->toBe('scrapyard-io-pwm')
        ->and($factory->addl_channels)->toBe([]);
});

test('channel(), name() and device() are fluent and set the expected properties', function () {
    $factory = new PWMConnectionFactory('fake-pwm');

    $result = $factory
        ->channel(2)
        ->name('servo')
        ->device('/dev/pwmchip0');

    expect($result)->toBe($factory)
        ->and($factory->channel)->toBe(2)
        ->and($factory->name)->toBe('servo')
        ->and($factory->pwm_chip)->toBe('/dev/pwmchip0');
});

test('create() throws when no pwm chip device has been set', function () {
    (new PWMConnectionFactory('fake-pwm'))->channel(0)->create();
})->throws(PWMChannelException::class, 'PWM Chip device is missing.');

test('create() throws when no channel has been set', function () {
    (new PWMConnectionFactory('fake-pwm'))->device('/dev/pwmchip0')->create();
})->throws(PWMChannelException::class, 'PWM Chip offset is missing.');

test('create() checks for a missing pwm chip device before a missing channel', function () {
    (new PWMConnectionFactory('fake-pwm'))->create();
})->throws(PWMChannelException::class, 'PWM Chip device is missing.');

test('create() forwards the configured chip, channel, name and additional channels to the driver adapter', function () {
    $factory = (new PWMConnectionFactory('fake-pwm'))
        ->device('/dev/pwmchip0')
        ->channel(2)
        ->name('servo');

    $driver = pwmFactoryDriverAdapter($factory);

    $result = $factory->create();

    expect($result)->toBeInstanceOf(PWMChannel::class)
        ->and($driver->buildConnectionCalls)->toBe([
            [
                'chip' => '/dev/pwmchip0',
                'channel' => 2,
                'consumer' => 'servo',
                'addl_channels' => [],
            ],
        ]);
});

test('createWith() sets the device and additional channels, then delegates to create()', function () {
    $factory = (new PWMConnectionFactory('fake-pwm'))->channel(0)->name('servos');
    $driver = pwmFactoryDriverAdapter($factory);
    // A driver asked to build multiple channels returns a MultiplePWMChannels
    // bus rather than a lone PWMChannel - createWith()'s return type is
    // strictly MultiplePWMChannels, so the fake must mirror that here.
    $driver->buildConnectionReturnValue = new MultiplePWMChannels([]);

    $result = $factory->createWith('/dev/pwmchip0', [1, 2]);

    expect($result)->toBe($driver->buildConnectionReturnValue)
        ->and($factory->pwm_chip)->toBe('/dev/pwmchip0')
        ->and($factory->addl_channels)->toBe([1, 2])
        ->and($driver->buildConnectionCalls)->toBe([
            [
                'chip' => '/dev/pwmchip0',
                'channel' => 0,
                'consumer' => 'servos',
                'addl_channels' => [1, 2],
            ],
        ]);
});
