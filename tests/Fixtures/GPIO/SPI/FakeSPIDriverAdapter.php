<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\SPI;

use GPIO\Contracts\Common\GPIOConnectionBus;
use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;
use GPIO\Contracts\SPI\SPIConnectionHandle as SPIConnectionHandleInterface;
use GPIO\Contracts\SPI\SPIDriverAdapter;
use GPIO\Contracts\SPI\SPIEndianness;
use GPIO\Contracts\SPI\SPIMode;
use GPIO\Contracts\SPI\SPITransport;
use GPIO\SPI\SPI;

/**
 * A recording SPI driver adapter: every method call is captured so tests
 * can assert exactly what the factory/transport forwarded to the driver,
 * mirroring FakeI2CDriverAdapter.
 */
class FakeSPIDriverAdapter implements SPIDriverAdapter
{
    /** Returned by buildConnection() unless overridden per-test. */
    public SPITransport|GPIOConnectionBus|null $buildConnectionReturnValue = null;

    /** Every argument list passed to buildConnection(). */
    public array $buildConnectionCalls = [];

    /** Value returned by every call to read(), unless overridden per-test. */
    public array|false $readReturnValue = false;

    /** Every [$len, $handle] pair passed to read(). */
    public array $readCalls = [];

    /** Value returned by every call to write(), unless overridden per-test. */
    public int $writeReturnValue = 0;

    /** Every [$data, $handle] pair passed to write(). */
    public array $writeCalls = [];

    /** Value returned by every call to transfer(), unless overridden per-test. */
    public array|false $transferReturnValue = false;

    /** Every [$data, $handle] pair passed to transfer(). */
    public array $transferCalls = [];

    /** Every handle passed to close(). */
    public array $closeCalls = [];

    public function buildConnection(
        int|string $master,
        int $chip_select,
        SPIMode $spi_mode,
        int $speed,
        int $bits_per_word = 8,
        SPIEndianness $endianness = SPIEndianness::MSB,
        int|string|null $gpio_chip = null,
        array $digital_pins = []
    ): SPITransport|GPIOConnectionBus {
        $this->buildConnectionCalls[] = [
            'master' => $master,
            'chip_select' => $chip_select,
            'spi_mode' => $spi_mode,
            'speed' => $speed,
            'bits_per_word' => $bits_per_word,
            'endianness' => $endianness,
            'gpio_chip' => $gpio_chip,
            'digital_pins' => $digital_pins,
        ];

        return $this->buildConnectionReturnValue ?? new SPI(new FakeSPIConnectionHandle, $this);
    }

    public function read(int $len, SPIConnectionHandleInterface $handle): array|false
    {
        $this->readCalls[] = [$len, $handle];

        return $this->readReturnValue;
    }

    public function write(array|string $data, SPIConnectionHandleInterface $handle): int
    {
        $this->writeCalls[] = [$data, $handle];

        return $this->writeReturnValue;
    }

    public function transfer(array|string $data, SPIConnectionHandleInterface $handle): array|false
    {
        $this->transferCalls[] = [$data, $handle];

        return $this->transferReturnValue;
    }

    public function close(GPIOConnectionHandleInterface $handle): void
    {
        $this->closeCalls[] = $handle;
    }
}
