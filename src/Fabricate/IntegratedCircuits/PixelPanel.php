<?php

namespace Fabricate\IntegratedCircuits;

use Fabricate\Contracts\Framebuffers\DumpedBuffer;
use Fabricate\Contracts\Framebuffers\FormatSpec;

abstract class PixelPanel extends Circuit
{
    abstract public function width(): int;

    abstract public function height(): int;

    abstract public function formatSpec(): FormatSpec;

    abstract public function transmit(DumpedBuffer $frame): void;
}
