<?php

namespace RealityInterface\Displays\Contracts\Applied\ePaper;

use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;

interface GenericElectronicInkDisplay
{
    /**
     * Write a channel-sorted frame to the panel's RAM(s) and refresh it.
     *
     * The buffer's {@see DumpedBuffer::$raw_data} is keyed by colour channel;
     * the panel writes each present channel to its matching RAM and skips any
     * colour the dump omits.
     */
    public function display(DumpedBuffer $buffer): void;
}
