<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\PWM\FakePWMConnectionHandle;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\PWM\FakePWMDriverAdapter;
use GPIO\Common\GPIOConnectionBus;
use GPIO\Contracts\PWM\PWMChannelBus;
use GPIO\PWM\MultiplePWMChannels;
use GPIO\PWM\PWMChannel;

test('it is a GPIOConnectionBus and a PWMChannelBus', function () {
    $bus = new MultiplePWMChannels([]);

    expect($bus)->toBeInstanceOf(GPIOConnectionBus::class)
        ->and($bus)->toBeInstanceOf(PWMChannelBus::class);
});

test('it stores the given channels as a public readonly property', function () {
    $channel = new PWMChannel(new FakePWMConnectionHandle, new FakePWMDriverAdapter);

    $bus = new MultiplePWMChannels(['servo' => $channel]);

    expect($bus->channels)->toBe(['servo' => $channel]);
});

test('getChannel() returns the channel registered under the given name', function () {
    $channel = new PWMChannel(new FakePWMConnectionHandle, new FakePWMDriverAdapter);
    $bus = new MultiplePWMChannels(['servo' => $channel]);

    expect($bus->getChannel('servo'))->toBe($channel);
});

test('getChannel() returns null when no channel is registered under the given name', function () {
    $bus = new MultiplePWMChannels([]);

    expect($bus->getChannel('missing'))->toBeNull();
});
