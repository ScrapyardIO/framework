<?php

namespace Waveforms\Carriers\GPIO\Contracts;

use Microscrap\Bindings\MPSSE\MPSSEContext;

/**
 * Implemented by USB carriers (SPI/I2C) that drive an FTDI MPSSE engine.
 *
 * The FT232H and friends expose a single MPSSE engine per USB interface, so the
 * spare GPIO pins (GPIOL0–3 / GPIOH0–7) must be driven through the very same
 * {@see MPSSEContext} that is already clocking SPI/I2C. Sharing the context lets
 * a GPIO bus co-reside with the data carrier instead of trying to claim the
 * device a second time.
 */
interface SharesMpsseContext
{
    public function mpsseContext(): MPSSEContext;
}
