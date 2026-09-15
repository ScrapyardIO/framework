<?php

use GeneralPurposeIO\Contracts\Core\ByteSource;
use GeneralPurposeIO\UART\Bus\UARTBus;
use ScrapyardIO\Tests\Support\Fakes\FakeUARTDriver;

it('is a byte source that drains only what is buffered', function () {
    $driver = new FakeUARTDriver();
    $driver->buffered = 'hello';
    $bus = new class($driver) extends UARTBus {};

    expect($bus)->toBeInstanceOf(ByteSource::class)
        ->and($bus->path())->toBe('fake:0')
        ->and($bus->pollBytes(3))->toBe('hel')
        ->and($bus->pollBytes())->toBe('lo')
        ->and($bus->pollBytes())->toBe('');
});
