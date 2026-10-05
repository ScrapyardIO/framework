<?php

use GeneralPurposeIO\Contracts\IntegratedCircuits\PipeablePanel;
use GeneralPurposeIO\Contracts\IntegratedCircuits\WindowAddressable;
use GeneralPurposeIO\Contracts\SPI\SPIException;
use GeneralPurposeIO\Contracts\SPI\WritesFromMemory;

it('declares a bus that writes spans read at addresses', function () {
    $method = new ReflectionMethod(WritesFromMemory::class, 'writeFrom');

    expect($method->getNumberOfParameters())->toBe(1)
        ->and((string) $method->getParameters()[0]->getType())->toBe('array')
        ->and((string) $method->getReturnType())->toBe('int');
});

it('declares a panel fed from memory as a window-addressable panel', function () {
    expect(is_subclass_of(PipeablePanel::class, WindowAddressable::class))->toBeTrue()
        ->and((string) (new ReflectionMethod(PipeablePanel::class, 'pixelBus'))->getReturnType())->toBe('?' . WritesFromMemory::class)
        ->and((new ReflectionMethod(PipeablePanel::class, 'openWindow'))->getNumberOfParameters())->toBe(4);
});

it('names the device when memory cannot be bit-reversed', function () {
    expect(SPIException::memoryNeedsNativeBitOrder('/dev/spidev0.0')->getMessage())
        ->toContain('/dev/spidev0.0')->toContain('reverses bits');
});
