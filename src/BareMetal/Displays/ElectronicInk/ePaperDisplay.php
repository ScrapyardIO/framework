<?php

namespace BareMetal\Displays\ElectronicInk;

use BareMetal\Contracts\Displays\Display as DisplayInterface;
use BareMetal\Contracts\Displays\ePaper;
use BareMetal\Contracts\Framebuffers\Framebuffer as FramebufferInterface;
use BareMetal\Displays\DisplayComponent;
use BareMetal\Framebuffers\FullFramebuffer;
use BareMetal\GFX\Renderer2D;

/**
 * Channel-aware: the IC's FormatSpec carries a ChannelPalette (one 1bpp plane
 * per ink colour), and its colours are the valid draw values — pixels are
 * plotted with eInkColor ints and sorted into planes at pack time. ePaper
 * refreshes are whole-panel affairs, so the default buffer is a
 * FullFramebuffer built from that palette-carrying spec.
 */
class ePaperDisplay extends DisplayComponent
{
    public function __construct(
        DisplayInterface&ePaper $display,
        ?FramebufferInterface $framebuffer = null,
        ?Renderer2D $renderer = null,
    ) {
        // An injected renderer draws on its own buffer, so the subclass
        // default only applies when the component builds the renderer itself.
        if (is_null($framebuffer) && is_null($renderer)) {
            $framebuffer = new FullFramebuffer(
                $display->width(),
                $display->height(),
                $display->formatSpec()
            );
        }

        parent::__construct($display, $framebuffer, $renderer);
    }
}
