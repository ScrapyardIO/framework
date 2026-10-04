<?php

use GeneralPurposeIO\Contracts\SPI\SPIException;
use ScrapyardIO\Tests\Fixtures\FakeSPIConnectionDriver;
use ScrapyardIO\Tests\Fixtures\FakeSPIConnectionFactory;
use ScrapyardIO\Tests\Fixtures\FakeSPIHandle;
use ScrapyardIO\Tests\Fixtures\FakeSPITransport;

/** A fake driver with bus 1 connected and registered. */
function spiDriver(): FakeSPIConnectionDriver
{
    $driver = new FakeSPIConnectionDriver;
    $driver->connectTo(1)->register();

    return $driver;
}

beforeEach(function () {
    FakeSPITransport::$log = [];
});

it('connects a bus through a factory and registers the handle it opens', function () {
    $driver = new FakeSPIConnectionDriver;
    $factory = $driver->connectTo(1);

    expect($factory)->toBeInstanceOf(FakeSPIConnectionFactory::class)
        ->and($driver->connections->has(1))->toBeFalse()
        ->and($factory->register())->toBe($driver)
        ->and($driver->connections->get(1))->toBeInstanceOf(FakeSPIHandle::class);
});

it('refuses to connect a bus that is already connected', function () {
    expect(fn () => spiDriver()->connectTo(1))->toThrow(SPIException::class, 'SPI device 1 is already connected.');
});

it('returns null for a slave on a bus that was never connected', function () {
    expect((new FakeSPIConnectionDriver)->device(1, 0))->toBeNull();
});

it('hands out one transport per chip select, all on the bus handle', function () {
    $driver = spiDriver();
    $a = $driver->device(1, 0);
    $b = $driver->device(1, 1);

    expect($a)->toBeInstanceOf(FakeSPITransport::class)
        ->and($driver->device(1, 0))->toBe($a)
        ->and($b)->not->toBe($a)
        ->and($a->chipSelect())->toBe(0)
        ->and($b->chipSelect())->toBe(1)
        ->and($a->handle())->toBe($driver->connections->get(1))
        ->and($b->handle())->toBe($driver->connections->get(1));
});

it('closes one slave without touching the others, and hands out a fresh one after', function () {
    $driver = spiDriver();
    $a = $driver->device(1, 0);
    $b = $driver->device(1, 1);

    $a->close();
    $fresh = $driver->device(1, 0);

    expect($a->closed())->toBeTrue()
        ->and($a->released)->toBeTrue()
        ->and($b->closed())->toBeFalse()
        ->and($driver->connections->get(1)->closed)->toBeFalse()
        ->and($fresh)->not->toBe($a)
        ->and($fresh->closed())->toBeFalse();
});

it('refuses every call on a closed slave', function () {
    $slave = spiDriver()->device(1, 0);
    $slave->close();
    $closed = 'SPI chip select 0 is closed.';

    expect(fn () => $slave->read(1))->toThrow(SPIException::class, $closed)
        ->and(fn () => $slave->write([0x01]))->toThrow(SPIException::class, $closed)
        ->and(fn () => $slave->transfer([0x01]))->toThrow(SPIException::class, $closed)
        ->and(fn () => $slave->writeRead([0x01], 1))->toThrow(SPIException::class, $closed)
        ->and(fn () => $slave->speed(1_000_000))->toThrow(SPIException::class, $closed)
        ->and(fn () => $slave->select(fn () => null))->toThrow(SPIException::class, $closed);
});

it('disconnect() closes every slave on the bus and the bus, leaves bus 11 alone, and lets the bus reopen', function () {
    $driver = new FakeSPIConnectionDriver;
    $driver->connectTo(1)->register();
    $driver->connectTo(11)->register();
    $a = $driver->device(1, 0);
    $b = $driver->device(1, 1);
    $c = $driver->device(11, 0);
    $handle = $driver->connections->get(1);

    $driver->disconnect(1);

    expect($a->closed())->toBeTrue()
        ->and($b->closed())->toBeTrue()
        ->and($handle->closed)->toBeTrue()
        ->and($driver->connections->has(1))->toBeFalse()
        ->and($c->closed())->toBeFalse()
        ->and($driver->connections->get(11)->closed)->toBeFalse();

    $driver->connectTo(1)->register();

    expect($driver->device(1, 0)->write([0x01]))->toBe(1);
});

it('holds chip select across every call inside select() and hands back what the body returns', function () {
    $slave = spiDriver()->device(1, 0);

    $result = $slave->select(function (FakeSPITransport $held): array {
        $held->write([0x01]);

        return $held->read(2);
    });

    expect($result)->toBe([0, 0])
        ->and(FakeSPITransport::$log)->toBe(['1:0:select', '1:0:write:01', '1:0:read:2', '1:0:deselect']);
});

it('deselects and frees the bus when the select() body throws', function () {
    $driver = spiDriver();
    $a = $driver->device(1, 0);
    $b = $driver->device(1, 1);

    expect(fn () => $a->select(function () {
        throw new RuntimeException('boom');
    }))->toThrow(RuntimeException::class, 'boom');

    expect($b->write([0x02]))->toBe(1)
        ->and(FakeSPITransport::$log)->toBe(['1:0:select', '1:0:deselect', '1:1:write:02']);
});

it('selects once when select() nests', function () {
    $slave = spiDriver()->device(1, 0);

    $slave->select(fn (FakeSPITransport $held) => $held->select(fn (FakeSPITransport $again) => $again->write([0x01])));

    expect(FakeSPITransport::$log)->toBe(['1:0:select', '1:0:write:01', '1:0:deselect']);
});

it('refuses another slave on the bus while one holds select(), and serves it after', function () {
    $driver = spiDriver();
    $a = $driver->device(1, 0);
    $b = $driver->device(1, 1);
    $held = 'SPI device 1 is held by chip select 0';

    $a->select(function () use ($b, $held) {
        expect(fn () => $b->write([0x02]))->toThrow(SPIException::class, $held)
            ->and(fn () => $b->select(fn () => null))->toThrow(SPIException::class, $held);
    });

    expect($b->write([0x02]))->toBe(1)
        ->and(FakeSPITransport::$log)->toBe(['1:0:select', '1:0:deselect', '1:1:write:02']);
});

it('closes a slave inside its own select() once chip select goes up', function () {
    $slave = spiDriver()->device(1, 0);

    $slave->select(function (FakeSPITransport $held) {
        $held->write([0x01]);
        $held->close();

        expect(fn () => $held->write([0x02]))->toThrow(SPIException::class, 'SPI chip select 0 is closed.');
    });

    expect($slave->closed())->toBeTrue()
        ->and(FakeSPITransport::$log)->toBe(['1:0:select', '1:0:write:01', '1:0:deselect', '1:0:release']);
});
