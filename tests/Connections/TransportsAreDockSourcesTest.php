<?php

use GeneralPurposeIO\Contracts\Core\ByteSource;
use GeneralPurposeIO\Contracts\Core\EdgeSource;
use GeneralPurposeIO\Contracts\Digital\DigitalEdgeEvent;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use GeneralPurposeIO\Core\IOPools\GPIOResourceDriver;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeDigitalIOConnectionDriver;
use ScrapyardIO\Tests\Support\Fakes\Connections\FakeUARTConnectionDriver;
use ScrapyardIO\Tests\Support\Fakes\FakePump;

/*
| The dock never names a platform. What it watches is whatever the base
| transports are: every DigitalInputTransport is an EdgeSource bound to its
| device by the driver that handed it out, every UARTTransport a ByteSource.
*/

it('hands out input pins that are edge sources bound to their device', function (): void {
    $driver = new FakeDigitalIOConnectionDriver;
    $driver->connectTo(0)->register();
    $driver->connectTo('ft232h')->register();

    $chip = $driver->input(0, 17);
    $usb = $driver->input('ft232h', 4);

    expect($chip)->toBeInstanceOf(EdgeSource::class)
        ->and($chip->device())->toBe(0)
        ->and($chip->offset())->toBe(17)
        ->and($usb->device())->toBe('ft232h')
        ->and($usb->offset())->toBe(4);
});

it('lets the dock watch a pin straight from the driver and name its mail by device and offset', function (): void {
    $driver = new FakeDigitalIOConnectionDriver;
    $driver->connectTo('ft232h')->register();
    $pin = $driver->input('ft232h', 4);
    $pin->pending = [new DigitalEdgeEvent(SignalEdge::RISING, 5)];
    $pump = new FakePump;

    (new GPIOResourceDriver($pump))->watch($pin)->tick();

    expect($pump->names())->toBe(['gpio.edge.ft232h.4']);
});

it('hands out uart ports that are byte sources', function (): void {
    $driver = new FakeUARTConnectionDriver;
    $driver->connectTo('/dev/ttyAMA0')->register();
    $port = $driver->device('/dev/ttyAMA0');
    $port->buffered = 'AT';
    $pump = new FakePump;

    expect($port)->toBeInstanceOf(ByteSource::class);

    (new GPIOResourceDriver($pump))->receive($port)->tick();

    expect($pump->names())->toBe(['gpio.uart./dev/ttyAMA0'])
        ->and($pump->mail[0]->bytes)->toBe('AT');
});
