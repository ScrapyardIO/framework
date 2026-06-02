<?php

namespace Waveforms\Carriers\SPI\Enums;

/**
 * Transport-neutral SPI bit order.
 *
 * The backing value mirrors the wire convention shared by the Linux spidev
 * SPI_LSB_FIRST mode bit and libmpsse's MSB/LSB defines — 0x00 for MSB-first,
 * 0x08 for LSB-first — so concrete builders can map it onto their backend
 * (native spidev mode word or MPSSE endianness) without a translation table.
 */
enum SPIEndianness: int
{
    /** Most-significant bit first — the SPI default for modes 0–3. */
    case MSB = 0x00;

    /** Least-significant bit first — required by devices such as the PN532. */
    case LSB = 0x08;
}
