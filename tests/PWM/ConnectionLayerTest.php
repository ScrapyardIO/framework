<?php

use GeneralPurposeIO\Contracts\PWM\PWMException;
use ScrapyardIO\Tests\Fixtures\FakePWMConnectionDriver;
use ScrapyardIO\Tests\Fixtures\FakePWMConnectionFactory;
use ScrapyardIO\Tests\Fixtures\FakePWMTransport;

/** A fake driver with chip 0 connected and registered. */
function pwmDriver(): FakePWMConnectionDriver
{
    $driver = new FakePWMConnectionDriver;
    $driver->connectTo(0)->register();

    return $driver;
}

beforeEach(function (): void {
    FakePWMTransport::$boards = [];
    FakePWMTransport::$log = [];
});

it('connects a chip through a factory and registers the handle it opens', function (): void {
    $driver = new FakePWMConnectionDriver;

    $factory = $driver->connectTo(0);

    expect($factory)->toBeInstanceOf(FakePWMConnectionFactory::class)
        ->and($factory->device)->toBe(0);

    $factory->register();

    expect($driver->connections->get(0)->device)->toBe(0);
});

it('refuses to connect a chip twice', function (): void {
    expect(fn () => pwmDriver()->connectTo(0))->toThrow(PWMException::class, 'PWM chip 0 is already connected.');
});

it('hands out nothing for a chip that is not connected', function (): void {
    expect((new FakePWMConnectionDriver)->device(0, 0))->toBeNull();
});

it('sets each attribute and answers what the channel reads back', function (): void {
    $servo = pwmDriver()->device(0, 0);

    expect($servo->setPeriod(20_000_000))->toBe(20_000_000)
        ->and($servo->setDutyCycle(1_500_000))->toBe(1_500_000)
        ->and($servo->setEnable(true))->toBeTrue()
        ->and($servo->setPolarity(true))->toBeTrue()
        ->and([$servo->getPeriod(), $servo->getDutyCycle(), $servo->getEnable(), $servo->getPolarity()])
        ->toBe([20_000_000, 1_500_000, true, true])
        ->and(FakePWMTransport::$log)->toBe([
            'bench:0:0:period=20000000',
            'bench:0:0:duty_cycle=1500000',
            'bench:0:0:enable=true',
            'bench:0:0:polarity=true',
        ]);
});

it('hands out the same transport for the same channel on the same chip', function (): void {
    $driver = pwmDriver();

    expect($driver->device(0, 0))->toBe($driver->device(0, 0))
        ->and($driver->device(0, 1))->not->toBe($driver->device(0, 0))
        ->and($driver->device(0, 1)->channel())->toBe(1);
});

it('closes only the channel: the chip and its other channels keep working', function (): void {
    $driver = pwmDriver();
    $servo = $driver->device(0, 0);
    $fan = $driver->device(0, 1);

    $servo->close();

    expect($servo->closed())->toBeTrue()
        ->and($servo->released)->toBeTrue()
        ->and($driver->connections->get(0)->closed)->toBeFalse()
        ->and($fan->setPeriod(40_000))->toBe(40_000);
});

it('refuses every call on a closed channel', function (): void {
    $servo = pwmDriver()->device(0, 0);
    $servo->close();
    $closed = 'PWM channel 0 is closed.';

    expect(fn () => $servo->getPeriod())->toThrow(PWMException::class, $closed)
        ->and(fn () => $servo->setPeriod(1))->toThrow(PWMException::class, $closed)
        ->and(fn () => $servo->getDutyCycle())->toThrow(PWMException::class, $closed)
        ->and(fn () => $servo->setDutyCycle(0))->toThrow(PWMException::class, $closed)
        ->and(fn () => $servo->getEnable())->toThrow(PWMException::class, $closed)
        ->and(fn () => $servo->setEnable(true))->toThrow(PWMException::class, $closed)
        ->and(fn () => $servo->getPolarity())->toThrow(PWMException::class, $closed)
        ->and(fn () => $servo->setPolarity(true))->toThrow(PWMException::class, $closed)
        ->and(FakePWMTransport::$log)->toBe([]);
});

it('releases a channel once, however often it is closed', function (): void {
    $servo = pwmDriver()->device(0, 0);
    $servo->close();
    $servo->released = false;

    $servo->close();

    expect($servo->released)->toBeFalse();
});

it('replaces a closed channel with a fresh one', function (): void {
    $driver = pwmDriver();
    $old = $driver->device(0, 0);
    $old->close();

    $new = $driver->device(0, 0);

    expect($new)->not->toBe($old)
        ->and($new->closed())->toBeFalse()
        ->and($new->setPeriod(20_000_000))->toBe(20_000_000);
});

it('disconnect() closes every channel on the chip, then the chip', function (): void {
    $driver = pwmDriver();
    $handle = $driver->connections->get(0);
    $servo = $driver->device(0, 0);
    $fan = $driver->device(0, 1);

    $driver->disconnect(0);

    expect($servo->closed())->toBeTrue()
        ->and($fan->closed())->toBeTrue()
        ->and($handle->closed)->toBeTrue()
        ->and($driver->connections->has(0))->toBeFalse()
        ->and($driver->device(0, 0))->toBeNull();

    $driver->connectTo(0)->register();

    expect($driver->device(0, 0)->setPeriod(20_000_000))->toBe(20_000_000);
});

it('disconnect() leaves another chip alone, even one whose number starts with the same digit', function (): void {
    $driver = new FakePWMConnectionDriver;
    $driver->connectTo(1)->register();
    $driver->connectTo(11)->register();
    $on_eleven = $driver->device(11, 0);

    $driver->disconnect(1);

    expect($on_eleven->closed())->toBeFalse()
        ->and($driver->connections->get(11)->closed)->toBeFalse()
        ->and($on_eleven->setPeriod(1_000))->toBe(1_000);
});

it('disconnect() on a chip that was never connected does nothing', function (): void {
    expect(fn () => (new FakePWMConnectionDriver)->disconnect(4))->not->toThrow(Throwable::class);
});
