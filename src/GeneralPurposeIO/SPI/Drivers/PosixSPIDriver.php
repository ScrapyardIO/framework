<?php

namespace GeneralPurposeIO\SPI\Drivers;

use Microscrap\Bindings\SPI\DataObjects\SPIDevice as PosixSPIDevice;
use Microscrap\Bindings\SPI\DataObjects\SPITransfer;

class PosixSPIDriver extends SPIDriver
{
    public function __construct(
        public readonly string $partial_path,
        public readonly  int $mode_flags,
        public readonly  int $speed,
        public readonly  int $bits_per_word,
    ) {}

    /**
     * @param PosixSPIDevice $chip_select
     * @param int $len
     * @return array|false
     */
    public function read($chip_select, int $len): array|false
    {
        $rx = spi_read($chip_select, $len);

        if ($rx === false) {
            return false;
        }

        return bytes2array($rx);
    }

    /**
     * @param PosixSPIDevice $chip_select
     * @param array|string $data
     * @return int
     */
    public function write($chip_select, array|string $data): int
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        return spi_write($chip_select, $data);
    }

    /**
     * @param PosixSPIDevice $chip_select
     * @param array|string $data
     * @return array|false
     */
    public function transfer($chip_select, array|string $data): array|false
    {
        if (is_array($data)) {
            $data = array2bytes($data);
        }

        $rx = spi_transfer($chip_select, new SPITransfer(tx: $data, len: strlen($data)));

        if ($rx === false) {
            return false;
        }

        return bytes2array($rx);
    }

    public function close(?PosixSPIDevice $device = null): void
    {
        if($device)
        {
            spi_close($device);
        }

    }
}