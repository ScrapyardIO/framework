<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\I2C;

use GPIO\Contracts\Common\GPIOConnectionBus;
use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;
use GPIO\Contracts\I2C\I2CConnectionHandle as I2CConnectionHandleInterface;
use GPIO\Contracts\I2C\I2CDriverAdapter;
use GPIO\Contracts\I2C\I2CTransport;
use GPIO\I2C\I2C;

/**
 * A recording I2C driver adapter: every method call is captured so tests
 * can assert exactly what the factory/transport forwarded to the driver,
 * mirroring FakeDigitalInputDriverAdapter/FakeDigitalOutputDriverAdapter.
 */
class FakeI2CDriverAdapter implements I2CDriverAdapter
{
    /** Returned by buildConnection() unless overridden per-test. */
    public I2CTransport|GPIOConnectionBus|null $buildConnectionReturnValue = null;

    /** Every argument list passed to buildConnection(). */
    public array $buildConnectionCalls = [];

    /** Value returned by every call to read(), unless overridden per-test. */
    public array|false $readReturnValue = false;

    /** Every [$slave_address, $len, $handle] triple passed to read(). */
    public array $readCalls = [];

    /** Value returned by every call to write(), unless overridden per-test. */
    public int $writeReturnValue = 0;

    /** Every [$slave_address, $data, $handle] triple passed to write(). */
    public array $writeCalls = [];

    /** Value returned by every call to writeRead(), unless overridden per-test. */
    public array|false $writeReadReturnValue = false;

    /** Every [$slave_address, $bytes_to_write, $bytes_to_read, $handle] tuple passed to writeRead(). */
    public array $writeReadCalls = [];

    /** Value returned by every call to bulkWrite(), unless overridden per-test. */
    public array|false $bulkWriteReturnValue = false;

    /** Every [$slave_address, $messages, $handle] triple passed to bulkWrite(). */
    public array $bulkWriteCalls = [];

    /** Every handle passed to close(). */
    public array $closeCalls = [];

    public function buildConnection(int|string $master, int $slave, int|string|null $gpio_chip = null, array $digital_pins = []): I2CTransport|GPIOConnectionBus
    {
        $this->buildConnectionCalls[] = [
            'master' => $master,
            'slave' => $slave,
            'gpio_chip' => $gpio_chip,
            'digital_pins' => $digital_pins,
        ];

        return $this->buildConnectionReturnValue ?? new I2C(new FakeI2CConnectionHandle($slave), $this);
    }

    public function writeRead(int $slave_address, string|array $bytes_to_write, int $bytes_to_read, I2CConnectionHandleInterface $handle): array|false
    {
        $this->writeReadCalls[] = [$slave_address, $bytes_to_write, $bytes_to_read, $handle];

        return $this->writeReadReturnValue;
    }

    public function bulkWrite(int $slave_address, string|array $messages, I2CConnectionHandleInterface $handle): array|false
    {
        $this->bulkWriteCalls[] = [$slave_address, $messages, $handle];

        return $this->bulkWriteReturnValue;
    }

    public function read(int $slave_address, int $len, I2CConnectionHandleInterface $handle): array|false
    {
        $this->readCalls[] = [$slave_address, $len, $handle];

        return $this->readReturnValue;
    }

    public function write(int $slave_address, string|array $data, I2CConnectionHandleInterface $handle): int
    {
        $this->writeCalls[] = [$slave_address, $data, $handle];

        return $this->writeReturnValue;
    }

    public function close(GPIOConnectionHandleInterface $handle): void
    {
        $this->closeCalls[] = $handle;
    }
}
