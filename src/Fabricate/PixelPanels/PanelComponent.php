<?php

namespace Fabricate\PixelPanels;

use Fabricate\Contracts\Framebuffers\DumpedBuffer;
use Fabricate\Contracts\Framebuffers\FormatSpec;
use Fabricate\Displays\EmbeddedDisplay;
use Fabricate\IntegratedCircuits\PixelPanel;

abstract class PanelComponent extends EmbeddedDisplay
{
    public function __construct(
        protected PixelPanel $panel,
    ) {
        parent::__construct($panel->width(), $panel->height());
    }

    abstract public static function buildWith(PixelPanel $panel): static;

    public function panel(): PixelPanel
    {
        return $this->panel;
    }

    public function formatSpec(): FormatSpec
    {
        return $this->panel->formatSpec();
    }

    public function transmit(DumpedBuffer $frame): void
    {
        $this->panel->transmit($frame);
    }
}
