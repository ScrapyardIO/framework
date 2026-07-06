<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO;

use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;
use GPIO\Contracts\Digital\DigitalPinBus;
use GPIO\Contracts\Digital\DigitalPinConnectionHandle as DigitalPinConnectionHandleInterface;
use GPIO\Contracts\Digital\DigitalPinDriverAdapter;
use GPIO\Contracts\Digital\DigitalPinTransport;
use RuntimeException;

/**
 * A no-op digital driver adapter, so tests can exercise carrier/factory
 * wiring without any real hardware driver (FTDI, MPSSE, etc.) being
 * installed.
 *
 * This used to also implement I2CDriverAdapter, SPIDriverAdapter and
 * UARTDriverAdapter, but each of those interfaces declares its own
 * incompatible buildConnection() signature - no single class can implement
 * all four. See FakeI2CDriverAdapter, FakeSPIDriverAdapter and
 * FakeUARTDriverAdapter for the other protocols.
 */
class FakeGPIODriverAdapter implements DigitalPinDriverAdapter
{
    public function buildConnection(int|string $device, int $pin, string $consumer): DigitalPinTransport|DigitalPinBus
    {
        throw new RuntimeException('FakeGPIODriverAdapter::buildConnection() is not implemented.');
    }

    public function read(int $pin, DigitalPinConnectionHandleInterface $handle): bool
    {
        return false;
    }

    public function close(GPIOConnectionHandleInterface $handle): void
    {
        //
    }
}
