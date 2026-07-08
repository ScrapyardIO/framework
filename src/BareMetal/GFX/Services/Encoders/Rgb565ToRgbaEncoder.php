<?php

namespace BareMetal\GFX\Services\Encoders;

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\Endianness;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;

/**
 * Decodes ROW_MAJOR RGB565 (B16) frames into RGBA8888.
 *
 * Each 5/6-bit channel is expanded to 8 bits by bit replication (31 → 255,
 * 63 → 255), the standard lossless-white expansion, with full alpha.
 */
class Rgb565ToRgbaEncoder extends RgbaEncoder
{
    protected function supportsSource(FormatSpec $source): bool
    {
        return ($source->pixel_format === PixelFormat::ROW_MAJOR)
            && ($source->bit_depth === BitDepth::B16);
    }

    protected function decodeToRgbaWords(DumpedBuffer $dump): array
    {
        $msb_first = ($dump->metadata->endianness !== Endianness::LSB);

        $words = [];
        $count = count($dump->raw_data);

        for ($i = 0; ($i + 1) < $count; $i += 2) {
            $word = $msb_first
                ? (($dump->raw_data[$i] << 8) | $dump->raw_data[$i + 1])
                : (($dump->raw_data[$i + 1] << 8) | $dump->raw_data[$i]);

            $r5 = ($word >> 11) & 0x1F;
            $g6 = ($word >> 5) & 0x3F;
            $b5 = $word & 0x1F;

            $r8 = ($r5 << 3) | ($r5 >> 2);
            $g8 = ($g6 << 2) | ($g6 >> 4);
            $b8 = ($b5 << 3) | ($b5 >> 2);

            $words[] = ($r8 << 24) | ($g8 << 16) | ($b8 << 8) | 0xFF;
        }

        return $words;
    }
}
