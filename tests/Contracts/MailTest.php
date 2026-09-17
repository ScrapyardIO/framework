<?php

use GeneralPurposeIO\Contracts\Core\Mail\DigitalEdgeOccurrence;
use GeneralPurposeIO\Contracts\Core\Mail\SourceFaultOccurrence;
use GeneralPurposeIO\Contracts\Core\Mail\TransferCompletion;
use GeneralPurposeIO\Contracts\Core\Mail\UARTBytesOccurrence;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use Voyager\Contracts\IOPools\Completion;
use Voyager\Contracts\IOPools\Occurrence;

it('names edge mail by device and offset, and by offset alone when the pin is unbound', function (): void {
    $bound = new DigitalEdgeOccurrence(0, 17, SignalEdge::RISING, 1_000);
    $usb = new DigitalEdgeOccurrence('ft232h', 4, SignalEdge::FALLING, 2_000);
    $loose = new DigitalEdgeOccurrence(null, 17, SignalEdge::RISING, 3_000);

    expect($bound)->toBeInstanceOf(Occurrence::class)
        ->and($bound->name)->toBe('gpio.edge.0.17')
        ->and($usb->name)->toBe('gpio.edge.ft232h.4')
        ->and($loose->name)->toBe('gpio.edge.17')
        ->and($bound->device)->toBe(0)
        ->and($bound->offset)->toBe(17)
        ->and($bound->edge)->toBe(SignalEdge::RISING)
        ->and($bound->timestamp_ns)->toBe(1_000);
});

it('names uart mail by path', function (): void {
    $mail = new UARTBytesOccurrence('/dev/ttyAMA0', "\x01\x02");

    expect($mail)->toBeInstanceOf(Occurrence::class)
        ->and($mail->name)->toBe('gpio.uart./dev/ttyAMA0')
        ->and($mail->bytes)->toBe("\x01\x02");
});

it('answers ok by the absence of an error', function (): void {
    $good = new TransferCompletion('aht20.measure', [1, 2]);
    $bad = new TransferCompletion('aht20.measure', null, new RuntimeException('nack'));

    expect($good)->toBeInstanceOf(Completion::class)
        ->and($good->name)->toBe('gpio.transfer.aht20.measure')
        ->and($good->ok())->toBeTrue()
        ->and($good->result)->toBe([1, 2])
        ->and($bad->ok())->toBeFalse()
        ->and($bad->error?->getMessage())->toBe('nack');
});

it('names a source fault by the source', function (): void {
    $fault = new SourceFaultOccurrence('edge.0.17', new RuntimeException('EIO'));

    expect($fault)->toBeInstanceOf(Occurrence::class)
        ->and($fault->name)->toBe('gpio.fault.edge.0.17')
        ->and($fault->error->getMessage())->toBe('EIO');
});
