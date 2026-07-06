<?php

namespace BareMetal\Framebuffers;

use BareMetal\Contracts\Framebuffers\FramebufferException;

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
