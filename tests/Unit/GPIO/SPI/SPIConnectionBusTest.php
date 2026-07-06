<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalInputDriverAdapter;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalPinConnectionHandle;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\SPI\FakeSPIConnectionHandle;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\SPI\FakeSPIDriverAdapter;
use GPIO\Common\GPIOConnectionBus;
use GPIO\Contracts\Digital\DigitalPinBus;
use GPIO\Digital\Input\DigitalInput;
use GPIO\Digital\MultipleDigitalPins;
use GPIO\SPI\SPI;
use GPIO\SPI\SPIConnectionBus;

test('it extends MultipleDigitalPins and is a GPIOConnectionBus and DigitalPinBus', function () {
    $bus = new SPIConnectionBus(new SPI(new FakeSPIConnectionHandle, new FakeSPIDriverAdapter));

    expect($bus)->toBeInstanceOf(MultipleDigitalPins::class)
        ->and($bus)->toBeInstanceOf(GPIOConnectionBus::class)
        ->and($bus)->toBeInstanceOf(DigitalPinBus::class);
});

test('it stores the given SPI transport as a public readonly property', function () {
    $spi = new SPI(new FakeSPIConnectionHandle, new FakeSPIDriverAdapter);

    $bus = new SPIConnectionBus($spi);

    expect($bus->spi)->toBe($spi);
});

test('it stores the given additional digital pins and getPin() finds them by name', function () {
    $spi = new SPI(new FakeSPIConnectionHandle, new FakeSPIDriverAdapter);
    $csPin = new DigitalInput(17, 'cs', new FakeDigitalPinConnectionHandle, new FakeDigitalInputDriverAdapter, 1000, false, false);

    $bus = new SPIConnectionBus($spi, ['cs' => $csPin]);

    expect($bus->pins)->toBe(['cs' => $csPin])
        ->and($bus->getPin('cs'))->toBe($csPin)
        ->and($bus->getPin('missing'))->toBeNull();
});

test('additional digital pins default to an empty array', function () {
    $bus = new SPIConnectionBus(new SPI(new FakeSPIConnectionHandle, new FakeSPIDriverAdapter));

    expect($bus->pins)->toBe([]);
});
