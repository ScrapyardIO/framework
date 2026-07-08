<?php

namespace BareMetal\Contracts\Displays;

use BareMetal\Contracts\Circuits\IntegratedCircuit;
use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;

interface Display extends IntegratedCircuit
{
    public function width(): int;
    public function height(): int;
    public function formatSpec(): FormatSpec;

    /**
     * Push one already-transcoded frame (matching {@see formatSpec()}) out to
     * the physical panel/window.
     */
    public function transmit(DumpedBuffer $frame): void;
}
