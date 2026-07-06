<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\I2C\FakeI2CConnectionHandle;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\I2C\FakeI2CDriverAdapter;
use GPIO\Contracts\I2C\I2CTransport;
use GPIO\I2C\I2C;

test('read() delegates to the driver using this connection\'s slave address and handle', function () {
    $handle = new FakeI2CConnectionHandle(0x42);
    $driver = new FakeI2CDriverAdapter;
    $driver->readReturnValue = [1, 2, 3];
    $i2c = new I2C($handle, $driver);

    $result = $i2c->read(3);

    expect($result)->toBe([1, 2, 3])
        ->and($driver->readCalls)->toBe([
            [0x42, 3, $handle],
        ])
        ->and($i2c)->toBeInstanceOf(I2CTransport::class);
});

test('read() returns false when the driver reports failure', function () {
    $handle = new FakeI2CConnectionHandle(0x42);
    $driver = new FakeI2CDriverAdapter;
    $driver->readReturnValue = false;
    $i2c = new I2C($handle, $driver);

    expect($i2c->read(3))->toBeFalse();
});

test('write() delegates to the driver using this connection\'s slave address and handle', function () {
    $handle = new FakeI2CConnectionHandle(0x42);
    $driver = new FakeI2CDriverAdapter;
    $driver->writeReturnValue = 2;
    $i2c = new I2C($handle, $driver);

    $result = $i2c->write([0xDE, 0xAD]);

    expect($result)->toBe(2)
        ->and($driver->writeCalls)->toBe([
            [0x42, [0xDE, 0xAD], $handle],
        ]);
});

test('write() accepts a string payload', function () {
    $handle = new FakeI2CConnectionHandle(0x42);
    $driver = new FakeI2CDriverAdapter;
    $i2c = new I2C($handle, $driver);

    $i2c->write('abc');

    expect($driver->writeCalls)->toBe([
        [0x42, 'abc', $handle],
    ]);
});

test('writeRead() delegates to the driver using this connection\'s slave address and handle', function () {
    $handle = new FakeI2CConnectionHandle(0x42);
    $driver = new FakeI2CDriverAdapter;
    $driver->writeReadReturnValue = [0x01];
    $i2c = new I2C($handle, $driver);

    $result = $i2c->writeRead([0x00], 1);

    expect($result)->toBe([0x01])
        ->and($driver->writeReadCalls)->toBe([
            [0x42, [0x00], 1, $handle],
        ]);
});

test('bulkWrite() delegates to the driver using this connection\'s slave address and handle', function () {
    $handle = new FakeI2CConnectionHandle(0x42);
    $driver = new FakeI2CDriverAdapter;
    $driver->bulkWriteReturnValue = [0x01, 0x02];
    $i2c = new I2C($handle, $driver);

    $result = $i2c->bulkWrite([[0x00, 0x01], [0x00, 0x02]]);

    expect($result)->toBe([0x01, 0x02])
        ->and($driver->bulkWriteCalls)->toBe([
            [0x42, [[0x00, 0x01], [0x00, 0x02]], $handle],
        ]);
});

test('every call uses the slave address from this connection\'s own handle, not a shared default', function () {
    $handleA = new FakeI2CConnectionHandle(0x08);
    $handleB = new FakeI2CConnectionHandle(0x77);
    $driver = new FakeI2CDriverAdapter;

    (new I2C($handleA, $driver))->read(1);
    (new I2C($handleB, $driver))->read(1);

    expect($driver->readCalls)->toBe([
        [0x08, 1, $handleA],
        [0x77, 1, $handleB],
    ]);
});

test('close() delegates to the driver using this connection\'s own handle', function () {
    $handle = new FakeI2CConnectionHandle(0x42);
    $unrelatedHandle = new FakeI2CConnectionHandle(0x08);
    $driver = new FakeI2CDriverAdapter;
    $i2c = new I2C($handle, $driver);

    $i2c->close($unrelatedHandle);

    expect($driver->closeCalls)->toBe([$handle]);
});
