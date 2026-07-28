<?php

namespace Fabricate\Framebuffers\Factory;

use Fabricate\Contracts\Framebuffers\FramebufferException;
use Fabricate\Framebuffers\Strategy\FullFramebuffer;

class FullFramebufferFactory extends FormatSpecFramebufferFactory
{
    /**
     * @throws FramebufferException
     */
    public function build(): FullFramebuffer
    {
        return new FullFramebuffer(
            $this->width,
            $this->height,
            $this->buildFormatSpec()
        );
    }
}
