<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO;

use GPIO\Contracts\Common\GPIOConnectionBus;
use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;
use GPIO\Contracts\I2C\I2CConnectionHandle as I2CConnectionHandleInterface;
use GPIO\Contracts\I2C\I2CDriverAdapter;
use GPIO\Contracts\I2C\I2CTransport;
use RuntimeException;

/**
 * A no-op I2C driver adapter, so tests can exercise carrier/factory wiring
 * without any real hardware driver (FTDI, MPSSE, etc.) being installed.
 */
class FakeI2CDriverAdapter implements I2CDriverAdapter
{
    public function buildConnection(int|string $master, int $slave, int|string|null $gpio_chip = null, array $digital_pins = []): I2CTransport|GPIOConnectionBus
    {
        throw new RuntimeException('FakeI2CDriverAdapter::buildConnection() is not implemented.');
    }

    public function writeRead(int $slave_address, string|array $bytes_to_write, int $bytes_to_read, I2CConnectionHandleInterface $handle): array|false
    {
        return false;
    }

    public function bulkWrite(int $slave_address, string|array $messages, I2CConnectionHandleInterface $handle): array|false
    {
        return false;
    }

    public function read(int $slave_address, int $len, I2CConnectionHandleInterface $handle): array|false
    {
        return false;
    }

    public function write(int $slave_address, string|array $data, I2CConnectionHandleInterface $handle): int
    {
        return 0;
    }

    public function close(GPIOConnectionHandleInterface $handle): void
    {
        //
    }
}
