<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\PWM\FakePWMConnectionHandle;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\PWM\FakePWMDriverAdapter;
use GPIO\Contracts\PWM\PWMChannelTransport;
use GPIO\PWM\PWMChannel;

test('setDutyCycle() delegates to the driver using this channel\'s own handle', function () {
    $handle = new FakePWMConnectionHandle;
    $driver = new FakePWMDriverAdapter;
    $driver->setDutyCycleReturnValue = 50;
    $channel = new PWMChannel($handle, $driver);

    $result = $channel->setDutyCycle(50);

    expect($result)->toBe(50)
        ->and($driver->setDutyCycleCalls)->toBe([
            [50, $handle],
        ])
        ->and($channel)->toBeInstanceOf(PWMChannelTransport::class);
});

test('getDutyCycle() delegates to the driver using this channel\'s own handle', function () {
    $handle = new FakePWMConnectionHandle;
    $driver = new FakePWMDriverAdapter;
    $driver->getDutyCycleReturnValue = 75;
    $channel = new PWMChannel($handle, $driver);

    $result = $channel->getDutyCycle();

    expect($result)->toBe(75)
        ->and($driver->getDutyCycleCalls)->toBe([$handle]);
});

test('setPeriod() delegates to the driver using this channel\'s own handle', function () {
    $handle = new FakePWMConnectionHandle;
    $driver = new FakePWMDriverAdapter;
    $driver->setPeriodReturnValue = 20_000;
    $channel = new PWMChannel($handle, $driver);

    $result = $channel->setPeriod(20_000);

    expect($result)->toBe(20_000)
        ->and($driver->setPeriodCalls)->toBe([
            [20_000, $handle],
        ]);
});

test('getPeriod() delegates to the driver using this channel\'s own handle', function () {
    $handle = new FakePWMConnectionHandle;
    $driver = new FakePWMDriverAdapter;
    $driver->getPeriodReturnValue = 20_000;
    $channel = new PWMChannel($handle, $driver);

    $result = $channel->getPeriod();

    expect($result)->toBe(20_000)
        ->and($driver->getPeriodCalls)->toBe([$handle]);
});

test('setEnable() delegates to the driver using this channel\'s own handle', function () {
    $handle = new FakePWMConnectionHandle;
    $driver = new FakePWMDriverAdapter;
    $driver->setEnableReturnValue = true;
    $channel = new PWMChannel($handle, $driver);

    $result = $channel->setEnable(true);

    expect($result)->toBeTrue()
        ->and($driver->setEnableCalls)->toBe([
            [true, $handle],
        ]);
});

test('getEnable() delegates to the driver using this channel\'s own handle', function () {
    $handle = new FakePWMConnectionHandle;
    $driver = new FakePWMDriverAdapter;
    $driver->getEnableReturnValue = true;
    $channel = new PWMChannel($handle, $driver);

    $result = $channel->getEnable();

    expect($result)->toBeTrue()
        ->and($driver->getEnableCalls)->toBe([$handle]);
});

test('setPolarity() delegates to the driver using this channel\'s own handle', function () {
    $handle = new FakePWMConnectionHandle;
    $driver = new FakePWMDriverAdapter;
    $driver->setPolarityReturnValue = 'inversed';
    $channel = new PWMChannel($handle, $driver);

    $result = $channel->setPolarity('inversed');

    expect($result)->toBe('inversed')
        ->and($driver->setPolarityCalls)->toBe([
            ['inversed', $handle],
        ]);
});

test('getPolarity() delegates to the driver using this channel\'s own handle', function () {
    $handle = new FakePWMConnectionHandle;
    $driver = new FakePWMDriverAdapter;
    $driver->getPolarityReturnValue = 'normal';
    $channel = new PWMChannel($handle, $driver);

    $result = $channel->getPolarity();

    expect($result)->toBe('normal')
        ->and($driver->getPolarityCalls)->toBe([$handle]);
});

test('each channel uses its own handle, not a shared default', function () {
    $handleA = new FakePWMConnectionHandle;
    $handleB = new FakePWMConnectionHandle;
    $driver = new FakePWMDriverAdapter;

    (new PWMChannel($handleA, $driver))->getDutyCycle();
    (new PWMChannel($handleB, $driver))->getDutyCycle();

    expect($driver->getDutyCycleCalls)->toBe([$handleA, $handleB]);
});

test('close() delegates to the driver using this channel\'s own handle', function () {
    $handle = new FakePWMConnectionHandle;
    $unrelatedHandle = new FakePWMConnectionHandle;
    $driver = new FakePWMDriverAdapter;
    $channel = new PWMChannel($handle, $driver);

    $channel->close($unrelatedHandle);

    expect($driver->closeCalls)->toBe([$handle]);
});
