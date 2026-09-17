<?php

use GeneralPurposeIO\Contracts\UART\DataBits;
use GeneralPurposeIO\Contracts\UART\FlowControl;
use GeneralPurposeIO\Contracts\UART\Parity;
use GeneralPurposeIO\Contracts\UART\StopBits;
use GeneralPurposeIO\Contracts\UART\UARTException;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeHandle;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeUARTConnectionDriver;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeUARTConnectionFactory;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeUARTTransport;

function uartDriver(): FakeUARTConnectionDriver
{
    $driver = new FakeUARTConnectionDriver;
    $driver->connectTo('/dev/ttyAMA0')->register();

    return $driver;
}

it('connects a port through a factory and registers the handle it opens', function (): void {
    $driver = new FakeUARTConnectionDriver;

    $factory = $driver->connectTo('/dev/ttyAMA0');

    expect($factory)->toBeInstanceOf(FakeUARTConnectionFactory::class)
        ->and($factory->device)->toBe('/dev/ttyAMA0')
        ->and($factory->register())->toBe($driver)
        ->and($driver->connections->get('/dev/ttyAMA0'))->toBeInstanceOf(FakeHandle::class);
});

it('refuses to connect a port that is already connected', function (): void {
    $driver = uartDriver();

    expect(fn () => $driver->connectTo('/dev/ttyAMA0'))->toThrow(UARTException::class, 'Device /dev/ttyAMA0 already connected');
});

it('starts a factory at 9600 8N1 with no flow control', function (): void {
    $factory = (new FakeUARTConnectionDriver)->connectTo('/dev/ttyAMA0');

    expect($factory->baud_rate)->toBe(9_600)
        ->and($factory->data_bits)->toBe(DataBits::EIGHT)
        ->and($factory->parity)->toBe(Parity::NONE)
        ->and($factory->stop_bits)->toBe(StopBits::ONE)
        ->and($factory->flow_control)->toBe(FlowControl::NONE);
});

it('carries line settings as fluent state, as enums or their int values', function (): void {
    $factory = (new FakeUARTConnectionDriver)->connectTo('/dev/ttyAMA0');

    $same = $factory->baud(115_200)->parity(Parity::EVEN)->stopBits(StopBits::TWO)->dataBits(DataBits::SEVEN)->flowControl(FlowControl::HARDWARE);

    expect($same)->toBe($factory)
        ->and($factory->baud_rate)->toBe(115_200)
        ->and($factory->parity)->toBe(Parity::EVEN)
        ->and($factory->stop_bits)->toBe(StopBits::TWO)
        ->and($factory->data_bits)->toBe(DataBits::SEVEN)
        ->and($factory->flow_control)->toBe(FlowControl::HARDWARE)
        ->and($factory->parity(1)->parity)->toBe(Parity::ODD)
        ->and($factory->stopBits(1)->stop_bits)->toBe(StopBits::ONE)
        ->and($factory->dataBits(5)->data_bits)->toBe(DataBits::FIVE)
        ->and($factory->flowControl(2)->flow_control)->toBe(FlowControl::SOFTWARE);
});

it('rejects line settings the enums do not define', function (): void {
    $factory = (new FakeUARTConnectionDriver)->connectTo('/dev/ttyAMA0');

    expect(fn () => $factory->dataBits(9))->toThrow(ValueError::class)
        ->and(fn () => $factory->stopBits(3))->toThrow(ValueError::class)
        ->and(fn () => $factory->parity(3))->toThrow(ValueError::class)
        ->and(fn () => $factory->flowControl(3))->toThrow(ValueError::class);
});

it('returns null for a port that was never connected', function (): void {
    expect((new FakeUARTConnectionDriver)->device('/dev/ttyAMA0'))->toBeNull();
});

it('hands out a transport over the registered port', function (): void {
    $driver = uartDriver();

    $port = $driver->device('/dev/ttyAMA0');

    expect($port)->toBeInstanceOf(FakeUARTTransport::class)
        ->and($port->path())->toBe('/dev/ttyAMA0')
        ->and($port->handle())->toBe($driver->connections->get('/dev/ttyAMA0'));
});

it('normalises int arrays to bytes on write and passes strings through untouched', function (): void {
    $port = uartDriver()->device('/dev/ttyAMA0');

    expect($port->write([0x41, 0x54, 0x0D]))->toBe(3)
        ->and($port->write("OK\r\n"))->toBe(4)
        ->and($port->writes)->toBe(["AT\r", "OK\r\n"]);
});

it('closing a port marks its handle closed', function (): void {
    $driver = uartDriver();

    $driver->device('/dev/ttyAMA0')->close();

    expect($driver->connections->get('/dev/ttyAMA0')->closed)->toBeTrue();
});
