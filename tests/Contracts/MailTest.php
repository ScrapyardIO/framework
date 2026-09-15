<?php

use GeneralPurposeIO\Contracts\Core\Mail\DigitalEdgeOccurrence;
use GeneralPurposeIO\Contracts\Core\Mail\SourceFaultOccurrence;
use GeneralPurposeIO\Contracts\Core\Mail\TransferCompletion;
use GeneralPurposeIO\Contracts\Core\Mail\UARTBytesOccurrence;
use GeneralPurposeIO\Contracts\Digital\SignalEdge;
use Voyager\Contracts\IOPools\Completion;
use Voyager\Contracts\IOPools\Occurrence;

it('names edge mail by offset', function () {
    $mail = new DigitalEdgeOccurrence(17, SignalEdge::RISING, 1_000);

    expect($mail)->toBeInstanceOf(Occurrence::class)
        ->and($mail->name)->toBe('gpio.edge.17')
        ->and($mail->edge)->toBe(SignalEdge::RISING)
        ->and($mail->timestamp_ns)->toBe(1_000);
});

it('names uart mail by path', function () {
    $mail = new UARTBytesOccurrence('/dev/ttyAMA0', "\x01\x02");

    expect($mail)->toBeInstanceOf(Occurrence::class)
        ->and($mail->name)->toBe('gpio.uart./dev/ttyAMA0')
        ->and($mail->bytes)->toBe("\x01\x02");
});

it('answers ok by the absence of an error', function () {
    $good = new TransferCompletion('aht20.measure', [1, 2, 3]);
    $bad = new TransferCompletion('aht20.measure', null, new RuntimeException('nope'));

    expect($good)->toBeInstanceOf(Completion::class)
        ->and($good->name)->toBe('gpio.transfer.aht20.measure')
        ->and($good->ok())->toBeTrue()
        ->and($good->result)->toBe([1, 2, 3])
        ->and($bad->ok())->toBeFalse()
        ->and($bad->error?->getMessage())->toBe('nope');
});

it('names a source fault by the source', function () {
    $mail = new SourceFaultOccurrence('edge.17', new RuntimeException('EIO'));

    expect($mail)->toBeInstanceOf(Occurrence::class)
        ->and($mail->name)->toBe('gpio.fault.edge.17')
        ->and($mail->error->getMessage())->toBe('EIO');
});
