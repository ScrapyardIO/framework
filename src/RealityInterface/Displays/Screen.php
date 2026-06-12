<?php

namespace RealityInterface\Displays;

use RealityInterface\Displays\Concerns\DrawingAPI;
use RealityInterface\Displays\Renderers\DisplayRenderer;
use RealityInterface\Displays\Services\BufferTranscoderService;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;

class Screen
{
    use DrawingAPI;

    protected BufferTranscoderService $transcoder;

    public function __construct(
        protected Display $display,
        protected DisplayRenderer $renderer,
    ) {
        $this->transcoder = new BufferTranscoderService(
            $this->display->getFormatSpec()
        );
    }

    public function render(bool $partial_refresh = false): void
    {
        $frame_queue = array_map(
            fn (DumpedBuffer $dumped_buffer) => $this->transcoder->transcode($dumped_buffer),
            $this->renderer->render()
        );

        // after we've built FrameBuffers supporting partial refresh,
        // @todo - add support for the $partial_refresh flag

        foreach ($frame_queue as $idx => $frame) {
            $this->display->transmit($frame);
        }
    }
}
