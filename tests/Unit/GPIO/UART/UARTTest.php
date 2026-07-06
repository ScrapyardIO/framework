<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\UART\FakeUARTConnectionHandle;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\UART\FakeUARTDriverAdapter;
use GPIO\Contracts\UART\UARTTransport;
use GPIO\UART\UART;

test('read() delegates to the driver using this connection\'s own handle', function () {
    $handle = new FakeUARTConnectionHandle;
    $driver = new FakeUARTDriverAdapter;
    $driver->readReturnValue = [1, 2, 3];
    $uart = new UART($handle, $driver);

    $result = $uart->read(3);

    expect($result)->toBe([1, 2, 3])
        ->and($driver->readCalls)->toBe([
            [3, $handle],
        ])
        ->and($uart)->toBeInstanceOf(UARTTransport::class);
});

test('read() returns false when the driver reports failure', function () {
    $handle = new FakeUARTConnectionHandle;
    $driver = new FakeUARTDriverAdapter;
    $driver->readReturnValue = false;
    $uart = new UART($handle, $driver);

    expect($uart->read(3))->toBeFalse();
});

test('write() delegates to the driver using this connection\'s own handle', function () {
    $handle = new FakeUARTConnectionHandle;
    $driver = new FakeUARTDriverAdapter;
    $driver->writeReturnValue = 5;
    $uart = new UART($handle, $driver);

    $result = $uart->write('hello');

    expect($result)->toBe(5)
        ->and($driver->writeCalls)->toBe([
            ['hello', $handle],
        ]);
});

test('write() accepts an array of bytes', function () {
    $handle = new FakeUARTConnectionHandle;
    $driver = new FakeUARTDriverAdapter;
    $uart = new UART($handle, $driver);

    $uart->write([0x01, 0x02]);

    expect($driver->writeCalls)->toBe([
        [[0x01, 0x02], $handle],
    ]);
});

test('flush() delegates to the driver using this connection\'s own handle', function () {
    $handle = new FakeUARTConnectionHandle;
    $driver = new FakeUARTDriverAdapter;
    $uart = new UART($handle, $driver);

    $uart->flush();

    expect($driver->flushCalls)->toBe([$handle]);
});

test('each connection uses its own handle, not a shared default', function () {
    $handleA = new FakeUARTConnectionHandle;
    $handleB = new FakeUARTConnectionHandle;
    $driver = new FakeUARTDriverAdapter;

    (new UART($handleA, $driver))->read(1);
    (new UART($handleB, $driver))->read(1);

    expect($driver->readCalls)->toBe([
        [1, $handleA],
        [1, $handleB],
    ]);
});

test('close() delegates to the driver using this connection\'s own handle', function () {
    $handle = new FakeUARTConnectionHandle;
    $unrelatedHandle = new FakeUARTConnectionHandle;
    $driver = new FakeUARTDriverAdapter;
    $uart = new UART($handle, $driver);

    $uart->close($unrelatedHandle);

    expect($driver->closeCalls)->toBe([$handle]);
});
