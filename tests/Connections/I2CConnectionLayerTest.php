<?php

use GeneralPurposeIO\Contracts\I2C\I2CException;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeHandle;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeI2CConnectionDriver;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeI2CConnectionFactory;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeI2CTransport;

/*
| The I2C connection layer: driver → factory → register → device → transport.
| Proven against fakes that subclass the abstract classes; no adapter package.
*/

function i2cDriver(): FakeI2CConnectionDriver
{
    $driver = new FakeI2CConnectionDriver;
    $driver->connectTo(1)->register();

    return $driver;
}

it('connects a bus through a factory and registers the handle it opens', function (): void {
    $driver = new FakeI2CConnectionDriver;

    $factory = $driver->connectTo(1);

    expect($factory)->toBeInstanceOf(FakeI2CConnectionFactory::class)
        ->and($factory->device)->toBe(1)
        ->and($driver->connections->has(1))->toBeFalse();

    expect($factory->register())->toBe($driver)
        ->and($driver->connections->get(1))->toBeInstanceOf(FakeHandle::class)
        ->and($driver->connections->get(1)->device)->toBe(1);
});

it('accepts a handle injected straight into register()', function (): void {
    $driver = new FakeI2CConnectionDriver;
    $handle = new FakeHandle('injected');

    $driver->register('injected', $handle);

    expect($driver->device('injected', 0x3C)->handle())->toBe($handle);
});

it('refuses to connect a bus that is already connected', function (): void {
    $driver = i2cDriver();

    expect(fn () => $driver->connectTo(1))->toThrow(I2CException::class, 'Device 1 already connected');
});

it('returns null for a slave on a bus that was never connected', function (): void {
    expect((new FakeI2CConnectionDriver)->device(1, 0x3C))->toBeNull();
});

it('hands out a transport bound to the slave address and the bus handle', function (): void {
    $driver = i2cDriver();

    $slave = $driver->device(1, 0x3C);

    expect($slave)->toBeInstanceOf(FakeI2CTransport::class)
        ->and($slave->address())->toBe(0x3C)
        ->and($slave->address)->toBe(0x3C)
        ->and($slave->handle())->toBe($driver->connections->get(1));
});

it('accepts the whole 7-bit address range and nothing outside it', function (): void {
    $driver = i2cDriver();

    expect($driver->device(1, 0x03)->address())->toBe(0x03)
        ->and($driver->device(1, 0x77)->address())->toBe(0x77)
        ->and(fn () => $driver->device(1, 0x02))->toThrow(I2CException::class, 'Only valid address')
        ->and(fn () => $driver->device(1, 0x78))->toThrow(I2CException::class, 'Only valid address')
        ->and(fn () => $driver->device(1, -1))->toThrow(I2CException::class, 'Only valid address');
});

it('normalises bulk messages: a string is one message', function (): void {
    $slave = i2cDriver()->device(1, 0x3C);

    expect($slave->bulkWrite("\x00\xAE"))->toBe([2])
        ->and($slave->writes)->toBe(["\x00\xAE"]);
});

it('normalises bulk messages: a flat int list is one message', function (): void {
    $slave = i2cDriver()->device(1, 0x3C);

    expect($slave->bulkWrite([0x00, 0xAE, 0xD5]))->toBe([3])
        ->and($slave->writes)->toBe(["\x00\xAE\xD5"]);
});

it('normalises bulk messages: nested lists and strings are separate messages', function (): void {
    $slave = i2cDriver()->device(1, 0x3C);

    expect($slave->bulkWrite([[0x00, 0xAE], "\x40\x01\x02", [0x81]]))->toBe([2, 3, 1])
        ->and($slave->writes)->toBe(["\x00\xAE", "\x40\x01\x02", "\x81"]);
});

it('normalises bulk messages: nothing in, nothing out', function (): void {
    $slave = i2cDriver()->device(1, 0x3C);

    expect($slave->bulkWrite([]))->toBe([])
        ->and($slave->writes)->toBe([]);
});

it('closing a slave marks the bus handle closed', function (): void {
    $driver = i2cDriver();
    $slave = $driver->device(1, 0x3C);

    $slave->close();

    expect($driver->connections->get(1)->closed)->toBeTrue()
        ->and($slave->probe())->toBeFalse();
});
