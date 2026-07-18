<?php

namespace GeneralPurposeIO\SPI\Drivers;

use Microscrap\Bindings\SPI\DataObjects\SPIDevice;
use Microscrap\Bindings\SPI\DataObjects\SPITransfer;

class PosixSPIDriver extends SPIDriver
{
    public function __construct(
        public readonly SPIDevice $device,
    ) {}

    public function close(): void
    {
        spi_close($this->device);
    }

    public function read(int $len): array|false
    {
        $rx = spi_read($this->device, $len);

        if ($rx === false) {
            return false;
        }

        return bytes2array($rx);
    }

    public function write(array|string $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        return spi_write($this->device, $data);
    }

    public function transfer(array|string $data): array|false
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        $rx = spi_transfer($this->device, new SPITransfer(tx: $data, len: strlen($data)));

        if ($rx === false) {
            return false;
        }

        return bytes2array($rx);
    }
}
