<?php

namespace Fabricate\Contracts\Rendering;

use Fabricate\Contracts\Displays\Display;
use Fabricate\Contracts\Framebuffers\Framebuffer;

interface GFXRenderer
{
    public function supportsDisplay(Display $display): bool;

    public function supportsFramebuffer(Framebuffer $framebuffer): bool;
}
