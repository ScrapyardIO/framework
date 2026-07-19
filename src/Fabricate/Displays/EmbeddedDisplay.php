<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Framebuffers\DumpedBuffer;

abstract class EmbeddedDisplay extends VisualOutputInterface
{
    abstract public function transmit(DumpedBuffer $frame): void;
}
