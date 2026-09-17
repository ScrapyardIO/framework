<?php

use GeneralPurposeIO\Contracts\PWM\PWMException;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeHandle;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakePWMConnectionDriver;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakePWMConnectionFactory;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakePWMTransport;

function pwmDriver(): FakePWMConnectionDriver
{
    $driver = new FakePWMConnectionDriver;
    $driver->connectTo(0)->register();

    return $driver;
}

it('connects a chip through a factory and registers the handle it opens', function (): void {
    $driver = new FakePWMConnectionDriver;

    $factory = $driver->connectTo(0);

    expect($factory)->toBeInstanceOf(FakePWMConnectionFactory::class)
        ->and($factory->device)->toBe(0)
        ->and($factory->register())->toBe($driver)
        ->and($driver->connections->get(0))->toBeInstanceOf(FakeHandle::class);
});

it('refuses to connect a chip that is already connected', function (): void {
    $driver = pwmDriver();

    expect(fn () => $driver->connectTo(0))->toThrow(PWMException::class, 'Device 0 already connected');
});

it('returns null for a channel on a chip that was never connected', function (): void {
    expect((new FakePWMConnectionDriver)->device(0, 0))->toBeNull();
});

it('hands out a transport per channel on the chip handle', function (): void {
    $driver = pwmDriver();

    $channel = $driver->device(0, 1);

    expect($channel)->toBeInstanceOf(FakePWMTransport::class)
        ->and($channel->channel())->toBe(1)
        ->and($channel->channel)->toBe(1)
        ->and($channel->handle())->toBe($driver->connections->get(0));
});

it('round-trips period, duty cycle, enable and polarity through the transport contract', function (): void {
    $channel = pwmDriver()->device(0, 0);

    expect($channel->setPeriod(20_000_000))->toBe(20_000_000)
        ->and($channel->getPeriod())->toBe(20_000_000)
        ->and($channel->setDutyCycle(1_500_000))->toBe(1_500_000)
        ->and($channel->getDutyCycle())->toBe(1_500_000)
        ->and($channel->setEnable(true))->toBeTrue()
        ->and($channel->getEnable())->toBeTrue()
        ->and($channel->setPolarity(true))->toBeTrue()
        ->and($channel->getPolarity())->toBeTrue();
});

it('closing a channel marks the chip handle closed', function (): void {
    $driver = pwmDriver();
    $channel = $driver->device(0, 0);
    $channel->setEnable(true);

    $channel->close();

    expect($channel->getEnable())->toBeFalse()
        ->and($driver->connections->get(0)->closed)->toBeTrue();
});
