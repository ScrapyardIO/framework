<?php

use GeneralPurposeIO\Contracts\UART\UARTException;
use GeneralPurposeIO\UART\UARTTransport;

it('read() hands back unread bytes at once, up to $length, and keeps the rest', function (): void {
    $port = uartPort();
    fwrite($port->wire, 'GPGGA');

    expect($port->read(3, 0))->toBe(bytes2array('GPG'))
        ->and($port->read(8, 0))->toBe(bytes2array('GA'));
});

it('read() waits for the first bytes to arrive', function (): void {
    $port = uartPort();
    $device = proc_open(['sh', '-c', 'sleep 0.05; printf abc'], [1 => $port->wire], $pipes);

    $started = hrtime(true);
    $bytes = $port->read(8, 1_000);
    $waited = (hrtime(true) - $started) / 1e6;
    proc_close($device);

    expect($bytes)->toBe(bytes2array('abc'))
        ->and($waited)->toBeGreaterThanOrEqual(40.0);
});

it('read(…, 0) never waits, and a timeout returns nothing once it passes', function (): void {
    $port = uartPort();

    $started = hrtime(true);
    $none = $port->read(8, 0);
    $instant = (hrtime(true) - $started) / 1e6;

    $started = hrtime(true);
    $late = $port->read(8, 40);
    $waited = (hrtime(true) - $started) / 1e6;

    expect($none)->toBe([])
        ->and($instant)->toBeLessThan(5.0)
        ->and($late)->toBe([])
        ->and($waited)->toBeGreaterThanOrEqual(35.0);
});

it('readUntil() hands back through the delimiter and keeps the rest unread', function (): void {
    $port = uartPort();
    fwrite($port->wire, "+OK\r\n+READY\r\n+ER");

    expect($port->readUntil("\r\n", 0))->toBe("+OK\r\n")
        ->and($port->readUntil("\r\n", 0))->toBe("+READY\r\n")
        ->and($port->readUntil("\r\n", 20))->toBeNull()
        ->and($port->pollBytes())->toBe('+ER');
});

it('readUntil() waits for a line that arrives in pieces', function (): void {
    $port = uartPort();
    $device = proc_open(['sh', '-c', 'printf \'$GNGGA,1\'; sleep 0.03; printf \'*00\r\n\''], [1 => $port->wire], $pipes);

    $line = $port->readUntil("\r\n", 1_000);
    proc_close($device);

    expect($line)->toBe("\$GNGGA,1*00\r\n");
});

it('readUntil() refuses an empty delimiter', function (): void {
    expect(fn () => uartPort()->readUntil('', 0))->toThrow(UARTException::class, 'readUntil() needs a delimiter of at least one byte.');
});

it('keeps the newest unread bytes and counts the ones it drops', function (): void {
    $port = uartPort();

    for ($i = 0; $i < 8; $i++) {
        fwrite($port->wire, str_repeat('x', 8_192));
        $port->pollBytes(0);
    }

    fwrite($port->wire, 'tail');
    $port->pollBytes(0);
    $kept = $port->pollBytes(UARTTransport::BUFFER_BYTES);

    expect($port->dropped())->toBe(4)
        ->and(strlen($kept))->toBe(UARTTransport::BUFFER_BYTES)
        ->and(str_ends_with($kept, 'xtail'))->toBeTrue();
});

it('write() hands every byte over in chunks of 256, in order', function (): void {
    $port = uartPort();
    $payload = random_bytes(600);

    expect($port->write($payload))->toBe(600)
        ->and($port->chunks)->toBe([256, 256, 88])
        ->and($port->sent())->toBe($payload);
});

it('write() takes a list of byte values', function (): void {
    $port = uartPort();

    expect($port->write([0x41, 0x54, 0x0D, 0x0A]))->toBe(4)
        ->and($port->sent())->toBe("AT\r\n");
});

it('write() waits for room and says how far it got when the timeout passes', function (): void {
    $port = uartPort();
    $port->room = 300;
    $started = hrtime(true);

    expect(fn () => $port->write(str_repeat('z', 600), 30))
        ->toThrow(UARTException::class, 'UART port bench took no more bytes before the timeout: 300 of 600 went out.');

    expect((hrtime(true) - $started) / 1e6)->toBeGreaterThanOrEqual(25.0)
        ->and($port->chunks)->toBe([256, 44])
        ->and(strlen($port->sent()))->toBe(300);
});

it('write() says so when the port fails', function (): void {
    $port = uartPort();
    $port->broken = true;

    expect(fn () => $port->write('AT'))->toThrow(UARTException::class, 'Could not write to UART port bench: 0 of 2 bytes went out.');
});

it('flush() discards unread bytes and what the port holds', function (): void {
    $port = uartPort();
    fwrite($port->wire, 'stale');
    $port->pollBytes(0);
    fwrite($port->wire, 'queued');

    $port->flush();

    expect($port->purged)->toBeTrue()
        ->and($port->read(8, 0))->toBe([]);
});

it('dtr() and rts() reach the adapter', function (): void {
    $port = uartPort();

    $port->dtr(true);
    $port->rts(false);

    expect($port->lines)->toBe(['dtr' => true, 'rts' => false]);
});

it('collects one drain a pass, so a device that never goes quiet cannot hold the caller', function (): void {
    $port = uartPort();
    $port->endless = 10_000;

    expect($port->read(4, 50))->toBe(bytes2array('x'))
        ->and($port->drains)->toBe(1);
});

it('never rounds a wait under a millisecond down to no wait at all', function (): void {
    $remaining = new ReflectionMethod(UARTTransport::class, 'remaining');

    expect($remaining->invoke(null, hrtime(true) + 500_000))->toBe(1)
        ->and($remaining->invoke(null, hrtime(true) - 1))->toBe(0)
        ->and($remaining->invoke(null, null))->toBe(-1);
});

it('carries how far a timed-out write got', function (): void {
    $port = uartPort();
    $port->room = 300;

    try {
        $port->write(str_repeat('z', 600), 30);
    } catch (UARTException $e) {
        $caught = $e;
    }

    expect($caught->sent)->toBe(300)
        ->and($caught->total)->toBe(600);
});
