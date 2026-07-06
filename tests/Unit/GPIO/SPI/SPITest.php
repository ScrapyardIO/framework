<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\SPI\FakeSPIConnectionHandle;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\SPI\FakeSPIDriverAdapter;
use GPIO\Contracts\SPI\SPITransport;
use GPIO\SPI\SPI;

test('transfer() delegates to the driver using this connection\'s own handle', function () {
    $handle = new FakeSPIConnectionHandle;
    $driver = new FakeSPIDriverAdapter;
    $driver->transferReturnValue = [0xAA, 0xBB];
    $spi = new SPI($handle, $driver);

    $result = $spi->transfer([0x01, 0x02]);

    expect($result)->toBe([0xAA, 0xBB])
        ->and($driver->transferCalls)->toBe([
            [[0x01, 0x02], $handle],
        ])
        ->and($spi)->toBeInstanceOf(SPITransport::class);
});

test('transfer() accepts a string payload', function () {
    $handle = new FakeSPIConnectionHandle;
    $driver = new FakeSPIDriverAdapter;
    $spi = new SPI($handle, $driver);

    $spi->transfer('abc');

    expect($driver->transferCalls)->toBe([
        ['abc', $handle],
    ]);
});

test('transfer() returns false when the driver reports failure', function () {
    $handle = new FakeSPIConnectionHandle;
    $driver = new FakeSPIDriverAdapter;
    $driver->transferReturnValue = false;
    $spi = new SPI($handle, $driver);

    expect($spi->transfer([0x00]))->toBeFalse();
});

test('read() delegates to the driver using this connection\'s own handle', function () {
    $handle = new FakeSPIConnectionHandle;
    $driver = new FakeSPIDriverAdapter;
    $driver->readReturnValue = [1, 2, 3];
    $spi = new SPI($handle, $driver);

    $result = $spi->read(3);

    expect($result)->toBe([1, 2, 3])
        ->and($driver->readCalls)->toBe([
            [3, $handle],
        ]);
});

test('write() delegates to the driver using this connection\'s own handle', function () {
    $handle = new FakeSPIConnectionHandle;
    $driver = new FakeSPIDriverAdapter;
    $driver->writeReturnValue = 2;
    $spi = new SPI($handle, $driver);

    $result = $spi->write([0xDE, 0xAD]);

    expect($result)->toBe(2)
        ->and($driver->writeCalls)->toBe([
            [[0xDE, 0xAD], $handle],
        ]);
});

test('each connection uses its own handle, not a shared default', function () {
    $handleA = new FakeSPIConnectionHandle;
    $handleB = new FakeSPIConnectionHandle;
    $driver = new FakeSPIDriverAdapter;

    (new SPI($handleA, $driver))->read(1);
    (new SPI($handleB, $driver))->read(1);

    expect($driver->readCalls)->toBe([
        [1, $handleA],
        [1, $handleB],
    ]);
});

test('close() delegates to the driver using this connection\'s own handle', function () {
    $handle = new FakeSPIConnectionHandle;
    $unrelatedHandle = new FakeSPIConnectionHandle;
    $driver = new FakeSPIDriverAdapter;
    $spi = new SPI($handle, $driver);

    $spi->close($unrelatedHandle);

    expect($driver->closeCalls)->toBe([$handle]);
});
