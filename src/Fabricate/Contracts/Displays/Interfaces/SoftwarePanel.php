<?php

namespace Fabricate\Contracts\Displays\Interfaces;

use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Contracts\Rendering\GFXRenderer;

interface SoftwarePanel extends PanelImplementation
{
    public function supportsRenderer(GFXRenderer $renderer): bool;

    public function supportsFramebuffer(Framebuffer $framebuffer): bool;
}