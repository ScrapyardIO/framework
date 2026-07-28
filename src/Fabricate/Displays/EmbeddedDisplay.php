<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Displays\EmbeddedDisplay as DisplayContract;
use Fabricate\Contracts\Displays\Interfaces\PanelIC as PanelInterface;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;

abstract class EmbeddedDisplay extends Display implements DisplayContract
{
    public function __construct(
        PanelInterface $panel
    ) {
        parent::__construct($panel);
    }


    public function flush(DumpedBuffer $frame): void
    {
        $this->panel->transmit($frame);
    }

    abstract public static function circuit(string $driver): static;
}