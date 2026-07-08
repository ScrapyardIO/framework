<?php

namespace BareMetal\Framebuffers;

use BareMetal\Contracts\Framebuffers\FramebufferException;

class PageSegmentBufferFactory extends FormatSpecFramebufferFactory
{
    /**
     * @throws FramebufferException
     */
    public function build(): PageSegmentBuffer
    {
        return new PageSegmentBuffer(
            $this->width,
            $this->height,
            $this->buildFormatSpec()
        );
    }
}
