<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\GFX;

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\GFX\BufferEncoder;

/**
 * Claims every ROW_MAJOR source and paints each pixel opaque magenta —
 * distinctive output so tests can tell it beat the built-in encoders.
 */
class MagentaEncoder implements BufferEncoder
{
    public function supports(FormatSpec $source, FormatSpec $target): bool
    {
        return $source->pixel_format === PixelFormat::ROW_MAJOR;
    }

    public function encode(DumpedBuffer $dump, FormatSpec $target): DumpedBuffer
    {
        $bytes = [];

        for ($i = 0; $i < ($dump->width * $dump->height); $i++) {
            array_push($bytes, 0xFF, 0x00, 0xFF, 0xFF);
        }

        return new DumpedBuffer(
            $dump->render_type,
            $target,
            $bytes,
            origin_x: $dump->origin_x,
            origin_y: $dump->origin_y,
            width: $dump->width,
            height: $dump->height,
        );
    }
}
