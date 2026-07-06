<?php

namespace GPIO\Contracts\SPI;

use GPIO\Contracts\Common\GPIOConnectionBus;
use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterContract;
use GPIO\Contracts\SPI\SPIConnectionHandle as SPIConnectionHandleInterface;

interface SPIDriverAdapter extends GPIODriverAdapterContract
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
    ): SPITransport|GPIOConnectionBus;

    public function read(int $len, SPIConnectionHandleInterface $handle): array|false;
    public function write(array|string $data, SPIConnectionHandleInterface $handle): int;
    public function transfer(array|string $data, SPIConnectionHandleInterface $handle): array|false;
}
