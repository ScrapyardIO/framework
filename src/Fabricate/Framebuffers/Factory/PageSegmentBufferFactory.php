<?php

namespace Fabricate\Framebuffers\Factory;

use Fabricate\Contracts\Framebuffers\FramebufferException;
use Fabricate\Framebuffers\PageSegmentBuffer;

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
