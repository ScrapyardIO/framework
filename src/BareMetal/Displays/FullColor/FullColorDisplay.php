<?php

namespace BareMetal\Displays\FullColor;

use BareMetal\Contracts\Displays\FullColorDisplay as FullColorDisplayInterface;
use BareMetal\Contracts\Framebuffers\Framebuffer as FramebufferInterface;
use BareMetal\Displays\DisplayComponent;
use BareMetal\Framebuffers\DirtyRegionsBuffer;
use BareMetal\GFX\Renderer2D;

class FullColorDisplay extends DisplayComponent
{
    public function __construct(
        FullColorDisplayInterface $display,
        ?FramebufferInterface $framebuffer = null,
        ?Renderer2D $renderer = null,
    ) {
        // An injected renderer draws on its own buffer, so the subclass
        // default only applies when the component builds the renderer itself.
        if (is_null($framebuffer) && is_null($renderer)) {
            $framebuffer = new DirtyRegionsBuffer(
                $display->width(),
                $display->height(),
                $display->formatSpec()
            );
        }

        parent::__construct($display, $framebuffer, $renderer);
    }
}
