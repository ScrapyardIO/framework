<?php

use GeneralPurposeIO\Contracts\SPI\SPIEndianness;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Contracts\SPI\SPIMode;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeHandle;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeSPIConnectionDriver;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeSPIConnectionFactory;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeSPITransport;

function spiDriver(): FakeSPIConnectionDriver
{
    $driver = new FakeSPIConnectionDriver;
    $driver->connectTo(0)->register();

    return $driver;
}

it('connects a master through a factory and registers the handle it opens', function (): void {
    $driver = new FakeSPIConnectionDriver;

    $factory = $driver->connectTo(0);

    expect($factory)->toBeInstanceOf(FakeSPIConnectionFactory::class)
        ->and($factory->device)->toBe(0)
        ->and($factory->register())->toBe($driver)
        ->and($driver->connections->get(0))->toBeInstanceOf(FakeHandle::class);
});

it('refuses to connect a master that is already connected', function (): void {
    $driver = spiDriver();

    expect(fn () => $driver->connectTo(0))->toThrow(SPIException::class, 'Device 0-* already connected');
});

it('starts a factory at mode 0, 800 kHz, MSB first, chip select 0', function (): void {
    $factory = (new FakeSPIConnectionDriver)->connectTo(0);

    expect($factory->spi_mode)->toBe(SPIMode::MODE_0)
        ->and($factory->speed)->toBe(800_000)
        ->and($factory->endianness)->toBe(SPIEndianness::MSB)
        ->and($factory->chip_select)->toBe(0);
});

it('carries mode, speed, endianness and chip select as fluent state, mode as enum or int', function (): void {
    $factory = (new FakeSPIConnectionDriver)->connectTo(0);

    $same = $factory->mode(3)->speed(8_000_000)->endianness(SPIEndianness::LSB)->chipSelect(1);

    expect($same)->toBe($factory)
        ->and($factory->spi_mode)->toBe(SPIMode::MODE_3)
        ->and($factory->speed)->toBe(8_000_000)
        ->and($factory->endianness)->toBe(SPIEndianness::LSB)
        ->and($factory->chip_select)->toBe(1)
        ->and($factory->mode(SPIMode::MODE_1)->spi_mode)->toBe(SPIMode::MODE_1);
});

it('rejects an SPI mode outside 0 to 3', function (): void {
    $factory = (new FakeSPIConnectionDriver)->connectTo(0);

    expect(fn () => $factory->mode(4))->toThrow(ValueError::class);
});

it('returns null for a device on a master that was never connected', function (): void {
    expect((new FakeSPIConnectionDriver)->device(0, 0))->toBeNull();
});

it('hands out a transport per chip select, defaulting to chip select 0', function (): void {
    $driver = spiDriver();

    $default = $driver->device(0);
    $second = $driver->device(0, 1);

    expect($default)->toBeInstanceOf(FakeSPITransport::class)
        ->and($default->chip_select)->toBe(0)
        ->and($second->chip_select)->toBe(1)
        ->and($second->handle())->toBe($driver->connections->get(0));
});

it('closing a device marks the master handle closed', function (): void {
    $driver = spiDriver();

    $driver->device(0)->close();

    expect($driver->connections->get(0)->closed)->toBeTrue();
});
