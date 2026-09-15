<?php

use GeneralPurposeIO\Contracts\UART\DataBits;
use GeneralPurposeIO\Contracts\UART\FlowControl;
use GeneralPurposeIO\Contracts\UART\Parity;
use GeneralPurposeIO\Contracts\UART\StopBits;
use GeneralPurposeIO\Contracts\UART\UARTDriver as UARTDriverContract;
use GeneralPurposeIO\UART\Adapters\FtdiUARTAdapter;
use GeneralPurposeIO\UART\Adapters\PosixUARTAdapter;
use GeneralPurposeIO\UART\Bus\UARTBus;
use GeneralPurposeIO\UART\Factory\UARTFactory;
use ScrapyardIO\Tests\Support\Fakes\FakeUARTDriver;

it('names autoload-safe adapter classes in config', function () {
    $config = require dirname(__DIR__, 2).'/config/gpio.php';

    expect($config['protocols']['uart']['adapters']['posix'])->toBe(PosixUARTAdapter::class)
        ->and($config['protocols']['uart']['adapters']['usb'])->toBe(FtdiUARTAdapter::class);
});

it('keeps factory defaults and fluent configuration', function () {
    $factory = new class extends UARTFactory {
        protected function assertReady(): void {}
        public function driver(): UARTDriverContract { return new FakeUARTDriver(); }
    };

    expect($factory->baud_rate)->toBe(9_600)
        ->and($factory->parity)->toBe(Parity::NONE)
        ->and($factory->stop_bits)->toBe(StopBits::ONE)
        ->and($factory->data_bits)->toBe(DataBits::EIGHT)
        ->and($factory->flow_control)->toBe(FlowControl::NONE);

    $result = $factory->baud(115_200)->parity(Parity::EVEN)->stopBits(StopBits::TWO)->dataBits(DataBits::SEVEN)->flowControl(FlowControl::HARDWARE);

    expect($result)->toBe($factory)
        ->and($factory->baud_rate)->toBe(115_200)
        ->and($factory->parity)->toBe(Parity::EVEN)
        ->and($factory->stop_bits)->toBe(StopBits::TWO)
        ->and($factory->data_bits)->toBe(DataBits::SEVEN)
        ->and($factory->flow_control)->toBe(FlowControl::HARDWARE);
});

it('delegates IO and lifecycle to the driver', function () {
    $driver = new FakeUARTDriver();
    $bus = new class($driver) extends UARTBus {};

    expect($bus->read(2))->toBe([0x41, 0x42])
        ->and($bus->write([0x41, 0x42]))->toBe(2);

    $bus->flush();
    $bus->close();

    expect($driver->reads)->toBe([2])
        ->and($driver->writes)->toBe([[0x41, 0x42]])
        ->and($driver->flushes)->toBe(1)
        ->and($driver->closes)->toBe(1)
        ->and($bus->driver())->toBe($driver);
});
