<?php

namespace GeneralPurposeIO\Contracts\SPI;

/**
 * A bus that sends bytes it reads at addresses, with no copy into PHP: the
 * rows of an ext-fb framebuffer, say. The spans go out in order under one
 * chip select. Addresses are trusted: each must hold its length in readable
 * bytes until writeFrom() returns.
 */
interface WritesFromMemory
{
    /**
     * @param  list<array{int, int}>  $spans  [address, length] each
     * @return int bytes written; -1 when the bus refused the write
     *
     * @throws SPIException when this bus cannot send from memory as it is configured
     */
    public function writeFrom(array $spans): int;
}
