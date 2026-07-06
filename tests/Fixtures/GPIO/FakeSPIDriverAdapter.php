<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\GPIO;

use GPIO\Contracts\Common\GPIOConnectionBus;
use GPIO\Contracts\Common\GPIOConnectionHandle as GPIOConnectionHandleInterface;
use GPIO\Contracts\SPI\SPIConnectionHandle as SPIConnectionHandleInterface;
use GPIO\Contracts\SPI\SPIDriverAdapter;
use GPIO\Contracts\SPI\SPIEndianness;
use GPIO\Contracts\SPI\SPIMode;
use GPIO\Contracts\SPI\SPITransport;
use RuntimeException;

/**
 * A no-op SPI driver adapter, so tests can exercise carrier/factory wiring
 * without any real hardware driver (FTDI, MPSSE, etc.) being installed.
 */
class FakeSPIDriverAdapter implements SPIDriverAdapter
{
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
        throw new RuntimeException('FakeSPIDriverAdapter::buildConnection() is not implemented.');
    }

    public function read(int $len, SPIConnectionHandleInterface $handle): array|false
    {
        return false;
    }

    public function write(array|string $data, SPIConnectionHandleInterface $handle): int
    {
        return 0;
    }

    public function transfer(array|string $data, SPIConnectionHandleInterface $handle): array|false
    {
        return false;
    }

    public function close(GPIOConnectionHandleInterface $handle): void
    {
        //
    }
}
