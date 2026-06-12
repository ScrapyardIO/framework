<?php

namespace RealityInterface\Displays\Contracts\Applied\Monochrome;

use RealityInterface\Displays\Contracts\Applied\DisplayInterface;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;

/**
 * The blast contract a monochrome panel exposes to the generic MonochromeDisplay.
 *
 * The Display layer packs the canonical grid into the panel's byte layout, then
 * points the panel at a region and hands it the bytes. How a panel addresses
 * that window (column/page registers vs. per-page commands) is its own concern.
 */
interface MonochromeDisplayInterface extends DisplayInterface
{
    public function display(DumpedBuffer $buffer): void;
}
