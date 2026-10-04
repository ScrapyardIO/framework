<?php

use GeneralPurposeIO\Contracts\UART\UARTReceived;
use ScrapyardIO\Tests\Fixtures\RecordingMailHandler;
use Voyager\IOPools\EventLoop;

it('mails each chunk of a watched port as gpio.uart.<device>', function () {
    $mail = new RecordingMailHandler;
    $loop = testLoop($mail);
    $port = uartPort($loop);

    $port->watch();
    $loop->at(0.005, fn () => fwrite($port->wire, "\$GNGGA\r\n"));
    $loop->at(0.02, fn () => fwrite($port->wire, "\$GNRMC\r\n"));
    $loop->at(0.04, fn () => $port->unwatch());
    $loop->run();

    expect($mail->events)->toHaveCount(2)
        ->and($mail->events[0])->toBeInstanceOf(UARTReceived::class)
        ->and($mail->events[0]->name())->toBe('gpio.uart.bench')
        ->and(array_map(fn (UARTReceived $received) => $received->bytes, $mail->events))->toBe(["\$GNGGA\r\n", "\$GNRMC\r\n"])
        ->and(array_map(fn (UARTReceived $received) => $received->seqno, $mail->events))->toBe([1, 2]);
});

it('a watched chunk is also there for read()', function () {
    $loop = testLoop();
    $port = uartPort($loop);

    $port->watch();
    $loop->at(0.005, fn () => fwrite($port->wire, '+OK'));
    $loop->at(0.02, fn () => $port->unwatch());
    $loop->run();

    expect($port->pollBytes())->toBe('+OK');
});

it('close() takes a watched port off the loop, so run() ends', function () {
    $loop = testLoop();
    $port = uartPort($loop);

    $port->watch();
    $loop->at(0.02, fn () => $port->close());

    $started = hrtime(true);
    $loop->run();

    expect((hrtime(true) - $started) / 1e6)->toBeLessThan(500.0)
        ->and($port->released)->toBeTrue();
});

it('a sampled port mails what it samples and stops sampling once unwatched', function () {
    $mail = new RecordingMailHandler;
    $loop = testLoop($mail);
    $port = uartPort($loop);
    $port->sample_every = 0.005;

    $port->watch();
    $loop->at(0.01, fn () => fwrite($port->wire, "+READY\r\n"));
    $loop->at(0.04, fn () => $port->unwatch());
    $loop->run();

    expect(array_map(fn (UARTReceived $received) => $received->bytes, $mail->events))->toBe(["+READY\r\n"])
        ->and($loop->registry->soonestDue())->toBeNull();
});

it('UARTReceived crosses a wire with its bytes intact', function () {
    $received = new UARTReceived('bench', "\x00\xff\r\n", 123_456, 7);

    $back = UARTReceived::fromData(json_decode(json_encode($received->toData()), true));

    expect($back->bytes)->toBe("\x00\xff\r\n")
        ->and($back->device)->toBe('bench')
        ->and($back->seqno)->toBe(7)
        ->and($back->uuid())->toBe('gpio.uart.bench.123456.7');
});
