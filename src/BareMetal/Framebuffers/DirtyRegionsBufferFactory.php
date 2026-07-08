<?php

namespace BareMetal\Framebuffers;

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
