<?php

namespace Waveforms\Carriers\SPI\Enums;

/**
 * Logical SPI clock mode (CPOL/CPHA). The backing value doubles as the Linux
 * spidev mode bitmask (CPHA = 0x01, CPOL = 0x02), and maps to the MPSSE SPI
 * modes by offsetting one (MPSSEMode::SPI0 = 1).
 */
enum SPIMode: int
{
    case MODE_0 = 0;
    case MODE_1 = 1;
    case MODE_2 = 2;
    case MODE_3 = 3;
}
