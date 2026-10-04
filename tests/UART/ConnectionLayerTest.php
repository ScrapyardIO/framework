<?php

use GeneralPurposeIO\Contracts\UART\UARTException;
use ScrapyardIO\Tests\Fixtures\FakeUARTConnectionDriver;
use ScrapyardIO\Tests\Fixtures\FakeUARTConnectionFactory;

/** A fake driver with port "bench" connected and registered. */
function uartDriver(): FakeUARTConnectionDriver
{
    $driver = new FakeUARTConnectionDriver;
    $driver->connectTo('bench')->register();

    return $driver;
}

it('connects a port through a factory and registers the handle it opens, at the factory\'s rate', function (): void {
    $driver = new FakeUARTConnectionDriver;

    $factory = $driver->connectTo('bench');

    expect($factory)->toBeInstanceOf(FakeUARTConnectionFactory::class);

    $factory->baud(9_600)->register();

    expect($driver->connections->get('bench')->baud)->toBe(9_600)
        ->and($driver->device('bench')->baud)->toBe(9_600);
});

it('refuses to connect a port twice', function (): void {
    expect(fn () => uartDriver()->connectTo('bench'))->toThrow(UARTException::class, 'UART port bench is already connected.');
});

it('hands out nothing for a port that is not connected', function (): void {
    expect((new FakeUARTConnectionDriver)->device('bench'))->toBeNull();
});

it('hands out one transport per port', function (): void {
    $driver = uartDriver();

    expect($driver->device('bench'))->toBe($driver->device('bench'));
});

it('close() closes the port and its connection, and connectTo() opens it again', function (): void {
    $driver = uartDriver();
    $handle = $driver->connections->get('bench');
    $port = $driver->device('bench');

    $port->close();

    expect($port->closed())->toBeTrue()
        ->and($port->released)->toBeTrue()
        ->and($handle->closed)->toBeTrue()
        ->and($driver->device('bench'))->toBeNull()
        ->and($driver->connections->has('bench'))->toBeFalse();

    $driver->connectTo('bench')->register();

    expect($driver->device('bench'))->not->toBe($port)
        ->and($driver->device('bench')->closed())->toBeFalse();
});

it('refuses every call on a closed port but a second close(), and releases it once', function (): void {
    $port = uartDriver()->device('bench');
    $port->close();
    $port->released = false;
    $closed = 'UART port bench is closed.';

    expect(fn () => $port->read(1, 0))->toThrow(UARTException::class, $closed)
        ->and(fn () => $port->readUntil("\n", 0))->toThrow(UARTException::class, $closed)
        ->and(fn () => $port->write('x'))->toThrow(UARTException::class, $closed)
        ->and(fn () => $port->pollBytes())->toThrow(UARTException::class, $closed)
        ->and(fn () => $port->flush())->toThrow(UARTException::class, $closed)
        ->and(fn () => $port->dtr(true))->toThrow(UARTException::class, $closed)
        ->and(fn () => $port->rts(true))->toThrow(UARTException::class, $closed)
        ->and(fn () => $port->close())->not->toThrow(Throwable::class)
        ->and($port->released)->toBeFalse();
});

it('disconnect() closes a port that was handed out', function (): void {
    $driver = uartDriver();
    $port = $driver->device('bench');

    $driver->disconnect('bench');

    expect($port->closed())->toBeTrue()
        ->and($port->handle->closed)->toBeTrue()
        ->and($driver->connections->has('bench'))->toBeFalse();
});

it('disconnect() closes a connection no port was handed out for', function (): void {
    $driver = uartDriver();
    $handle = $driver->connections->get('bench');

    $driver->disconnect('bench');

    expect($handle->closed)->toBeTrue()
        ->and($driver->connections->has('bench'))->toBeFalse();
});

it('disconnect() on a port that was never connected does nothing', function (): void {
    expect(fn () => (new FakeUARTConnectionDriver)->disconnect('bench'))->not->toThrow(Throwable::class);
});
