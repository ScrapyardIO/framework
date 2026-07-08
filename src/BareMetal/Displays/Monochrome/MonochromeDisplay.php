<?php

namespace BareMetal\Displays\Monochrome;

use BareMetal\Contracts\Displays\MonochromeDisplay as MonochromeDisplayInterface;
use BareMetal\Contracts\Framebuffers\Framebuffer as FramebufferInterface;
use BareMetal\Displays\DisplayComponent;
use BareMetal\Framebuffers\PageSegmentBuffer;
use BareMetal\GFX\Renderer2D;

class MonochromeDisplay extends DisplayComponent
{
    public function __construct(
        MonochromeDisplayInterface $display,
        ?FramebufferInterface $framebuffer = null,
        ?Renderer2D $renderer = null,
    ) {
        // An injected renderer draws on its own buffer, so the subclass
        // default only applies when the component builds the renderer itself.
        if (is_null($framebuffer) && is_null($renderer)) {
            $framebuffer = new PageSegmentBuffer(
                $display->width(),
                $display->height(),
                $display->formatSpec()
            );
        }

        parent::__construct($display, $framebuffer, $renderer);
    }
}
