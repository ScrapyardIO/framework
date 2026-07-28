<?php

namespace Fabricate\Rendering;

use Fabricate\Contracts\Displays\Display;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\Rendering\GFXRenderer;

abstract class Renderer implements GFXRenderer
{
    public function supportsDisplay(Display $display): bool
    {
        return true;
    }

    public function supportsFramebuffer(Framebuffer $framebuffer): bool
    {
        return true;
    }
}
