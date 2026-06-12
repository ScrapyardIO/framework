<?php

namespace ScrapyardIO\NutsAndBolts\Buffers\Factory;

use Exception;
use ScrapyardIO\NutsAndBolts\Buffers\FullFrameBuffer;

class FullFrameBufferFactory extends FSBFFactory
{
    /**
     * @throws Exception
     */
    public function build(): FullFrameBuffer
    {
        return new FullFrameBuffer(
            $this->width,
            $this->height,
            $this->buildFormatSpec()
        );
    }
}
