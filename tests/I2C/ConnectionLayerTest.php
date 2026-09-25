<?php

use GeneralPurposeIO\Contracts\I2C\I2CException;
use ScrapyardIO\Tests\Fixtures\FakeI2CConnectionDriver;
use ScrapyardIO\Tests\Fixtures\FakeI2CConnectionFactory;
use ScrapyardIO\Tests\Fixtures\FakeI2CHandle;
use ScrapyardIO\Tests\Fixtures\FakeI2CTransport;

/** A fake driver with bus 1 connected and registered. */
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
        ->and($driver->connections->get(1))->toBeInstanceOf(FakeI2CHandle::class)
        ->and($driver->connections->get(1)->device)->toBe(1);
});

it('accepts a handle injected straight into register()', function (): void {
    $driver = new FakeI2CConnectionDriver;
    $handle = new FakeI2CHandle('injected');

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
        ->and(fn () => $driver->device(1, 0x02))->toThrow(I2CException::class, 'Only valid address between 0x03 and 0x77 allowed')
        ->and(fn () => $driver->device(1, 0x78))->toThrow(I2CException::class, 'Only valid address between 0x03 and 0x77 allowed')
        ->and(fn () => $driver->device(1, -1))->toThrow(I2CException::class, 'Only valid address between 0x03 and 0x77 allowed');
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

it('hands out the same transport for the same slave on the same bus', function (): void {
    $driver = i2cDriver();

    expect($driver->device(1, 0x3C))->toBe($driver->device(1, 0x3C))
        ->and($driver->device(1, 0x3D))->not->toBe($driver->device(1, 0x3C));
});

it('closes only the slave: the bus and its other slaves keep working', function (): void {
    $driver = i2cDriver();
    $panel = $driver->device(1, 0x3C);
    $fan = $driver->device(1, 0x21);

    $panel->close();

    expect($panel->closed())->toBeTrue()
        ->and($panel->released)->toBeTrue()
        ->and($driver->connections->get(1)->closed)->toBeFalse()
        ->and($fan->write([0x03, 0x01]))->toBe(2);
});

it('refuses every call on a closed slave', function (): void {
    $slave = i2cDriver()->device(1, 0x3C);
    $slave->close();

    expect(fn () => $slave->probe())->toThrow(I2CException::class, 'I2C slave 0x3C is closed.')
        ->and(fn () => $slave->read(1))->toThrow(I2CException::class, 'I2C slave 0x3C is closed.')
        ->and(fn () => $slave->write([0x00]))->toThrow(I2CException::class, 'I2C slave 0x3C is closed.')
        ->and(fn () => $slave->writeRead([0x00], 1))->toThrow(I2CException::class, 'I2C slave 0x3C is closed.')
        ->and(fn () => $slave->bulkWrite([0x00]))->toThrow(I2CException::class, 'I2C slave 0x3C is closed.');
});

it('releases a slave once, however often it is closed', function (): void {
    $slave = i2cDriver()->device(1, 0x3C);

    $slave->close();
    $slave->released = false;
    $slave->close();

    expect($slave->released)->toBeFalse();
});

it('hands out a fresh transport once the old one is closed', function (): void {
    $driver = i2cDriver();
    $old = $driver->device(1, 0x3C);
    $old->close();

    $fresh = $driver->device(1, 0x3C);

    expect($fresh)->not->toBe($old)
        ->and($fresh->closed())->toBeFalse()
        ->and($fresh->probe())->toBeTrue();
});

it('disconnect() closes every slave and the bus, and the bus connects again', function (): void {
    $driver = i2cDriver();
    $handle = $driver->connections->get(1);
    $panel = $driver->device(1, 0x3C);
    $fan = $driver->device(1, 0x21);

    $driver->disconnect(1);

    expect($panel->closed())->toBeTrue()
        ->and($fan->closed())->toBeTrue()
        ->and($handle->closed)->toBeTrue()
        ->and($driver->connections->has(1))->toBeFalse()
        ->and($driver->device(1, 0x3C))->toBeNull();

    $driver->connectTo(1)->register();

    expect($driver->device(1, 0x3C)->probe())->toBeTrue();
});

it('disconnect() leaves another bus alone, even one whose number starts with the same digit', function (): void {
    $driver = i2cDriver();
    $driver->connectTo(11)->register();
    $on_eleven = $driver->device(11, 0x3C);

    $driver->disconnect(1);

    expect($on_eleven->closed())->toBeFalse()
        ->and($driver->connections->get(11)->closed)->toBeFalse()
        ->and($on_eleven->probe())->toBeTrue();
});

it('disconnect() on a bus that was never connected does nothing', function (): void {
    expect(fn () => (new FakeI2CConnectionDriver)->disconnect(4))->not->toThrow(Throwable::class);
});

it('refuses a message longer than 8192 bytes before it reaches the bus', function (): void {
    ScrapyardIO\Tests\Fixtures\FakeI2CTransport::$log = [];
    $slave = i2cDriver()->device(1, 0x3C);
    $long = str_repeat("\x00", 8193);
    $limit = '8193 bytes is longer than the 8192-byte I2C message limit.';

    expect(fn () => $slave->write($long))->toThrow(I2CException::class, $limit)
        ->and(fn () => $slave->read(8193))->toThrow(I2CException::class, $limit)
        ->and(fn () => $slave->writeRead($long, 1))->toThrow(I2CException::class, $limit)
        ->and(fn () => $slave->writeRead([0x00], 8193))->toThrow(I2CException::class, $limit)
        ->and(fn () => $slave->bulkWrite([[0x00], $long]))->toThrow(I2CException::class, $limit)
        ->and($slave->write(str_repeat("\x00", 8192)))->toBe(8192)
        ->and(ScrapyardIO\Tests\Fixtures\FakeI2CTransport::$log)->toHaveCount(1);
});
