<?php

namespace Waveforms\Carriers\SPI\API\Native;

use Microscrap\Bindings\SPI\DataObjects\SPIDevice as SpiBus;
use Microscrap\Bindings\SPI\DataObjects\SPITransfer;
use Waveforms\Carriers\SPI\SPIDevice;

class NativeSPIDevice extends SPIDevice
{
    public function __construct(
        protected SpiBus $bus
    ) {}

    public function transfer(string|array $data): array|false
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        $rx = spi_transfer($this->bus, new SPITransfer(tx: $data, len: strlen($data)));

        if ($rx === false) {
            return false;
        }

        return bytes2array($rx);
    }

    public function read(int $len): array|false
    {
        $rx = spi_read($this->bus, $len);

        if ($rx === false) {
            return false;
        }

        return bytes2array($rx);
    }

    public function write(string|array $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        return spi_write($this->bus, $data);
    }

    public function close(): void
    {
        spi_close($this->bus);
    }
}
