<?php

namespace BareMetal\GFX\Services\Encoders;

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\Endianness;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\GFX\BufferEncoder;
use BareMetal\Contracts\GFX\TranscoderException;

/**
 * Shared plumbing for encoders that emit ROW_MAJOR RGBA8888 (B32) frames.
 *
 * Subclasses decode their source layout into a flat row-major list of
 * 0xRRGGBBAA words; this base slices those words into bytes per the target
 * endianness (MSB, the default, leads with the red byte).
 */
abstract class RgbaEncoder implements BufferEncoder
{
    /**
     * @return array<int, int> Flat row-major 0xRRGGBBAA words
     */
    abstract protected function decodeToRgbaWords(DumpedBuffer $dump): array;

    abstract protected function supportsSource(FormatSpec $source): bool;

    public function supports(FormatSpec $source, FormatSpec $target): bool
    {
        return $this->targetsRgba($target) && $this->supportsSource($source);
    }

    public function encode(DumpedBuffer $dump, FormatSpec $target): DumpedBuffer
    {
        return new DumpedBuffer(
            $dump->render_type,
            $target,
            $this->packRgbaWords($this->decodeToRgbaWords($dump), $target),
            origin_x: $dump->origin_x,
            origin_y: $dump->origin_y,
            width: $dump->width,
            height: $dump->height,
        );
    }

    protected function targetsRgba(FormatSpec $target): bool
    {
        return ($target->pixel_format === PixelFormat::ROW_MAJOR)
            && ($target->bit_depth === BitDepth::B32);
    }

    /**
     * @param  array<int, int>  $words
     * @return array<int, int>
     */
    protected function packRgbaWords(array $words, FormatSpec $target): array
    {
        $msb_first = ($target->endianness !== Endianness::LSB);

        $bytes = [];

        foreach ($words as $word) {
            for ($i = 0; $i < 4; $i++) {
                $shift = $msb_first ? ((3 - $i) * 8) : ($i * 8);
                $bytes[] = ($word >> $shift) & 0xFF;
            }
        }

        return $bytes;
    }

    /**
     * @return array{0: int, 1: int}
     *
     * @throws TranscoderException
     */
    protected function dimensions(DumpedBuffer $dump): array
    {
        if (is_null($dump->width) || is_null($dump->height)) {
            throw TranscoderException::missingDimensions();
        }

        return [$dump->width, $dump->height];
    }
}
