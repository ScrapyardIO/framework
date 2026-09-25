<?php

namespace GeneralPurposeIO\SPI;

use GeneralPurposeIO\Contracts\SPI\SPIEndianness;
use GeneralPurposeIO\Contracts\SPI\SPIMode;

/** What a bus was opened with, as a worker needs it to open the same bus the same way. Word size matters to spidev only. */
final readonly class SPIBusSettings
{
    public function __construct(
        public SPIMode $mode = SPIMode::MODE_0,
        public int $speed = 800_000,
        public SPIEndianness $endianness = SPIEndianness::MSB,
        public int $bits_per_word = 8,
    ) {}
}
