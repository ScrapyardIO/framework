<?php

namespace GeneralPurposeIO\Contracts\IntegratedCircuits;

use GeneralPurposeIO\Contracts\SPI\WritesFromMemory;

/**
 * A panel that can be fed straight from memory. openWindow() sets the window,
 * starts the memory write and leaves the panel taking data; the window's
 * bytes, packed per the chip's formatSpec(), then go out on pixelBus() row
 * by row. Surface's DirectEDisplay drives it.
 */
interface PipeablePanel extends WindowAddressable
{
    public function openWindow(int $x, int $y, int $width, int $height): void;

    /** The bus pixel bytes go out on; null when the chip's bus cannot write from memory (I2C, an offloaded bus, MPSSE). */
    public function pixelBus(): ?WritesFromMemory;
}
