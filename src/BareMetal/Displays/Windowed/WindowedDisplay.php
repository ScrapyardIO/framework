<?php

namespace BareMetal\Displays\Windowed;

use BareMetal\Contracts\Displays\Display as DisplayInterface;
use BareMetal\Contracts\Displays\WindowedDisplay as WindowedDisplayContract;
use BareMetal\Contracts\Framebuffers\Framebuffer as FramebufferInterface;
use BareMetal\Displays\DisplayComponent;
use BareMetal\GFX\Renderer2D;

/**
 * The component for WindowedDisplay-tagged ICs (SDL3 windows and friends).
 *
 * Defaults to the sdl3 rendering library; with no framebuffer injected the
 * base resolution asks that renderer for its preferred framebuffer built from
 * the window's FormatSpec, keeping the flush path transcode-free.
 */
class WindowedDisplay extends DisplayComponent
{
    public function __construct(
        DisplayInterface&WindowedDisplayContract $display,
        ?FramebufferInterface $framebuffer = null,
        ?Renderer2D $renderer = null,
    ) {
        parent::__construct($display, $framebuffer, $renderer);
    }

    protected function defaultRendererName(): string
    {
        return 'sdl3';
    }
}
