<?php

namespace Fabricate\Framebuffers\Factory;

use Exception;
use Fabricate\Framebuffers\DirtyRegionsBuffer;

class DirtyRegionsBufferFactory extends FormatSpecFramebufferFactory
{
    /**
     * @throws Exception
     */
    public function build(): DirtyRegionsBuffer
    {
        return new DirtyRegionsBuffer(
            $this->width,
            $this->height,
            $this->buildFormatSpec()
        );
    }
}
