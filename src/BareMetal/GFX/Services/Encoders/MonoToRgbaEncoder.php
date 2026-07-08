<?php

namespace BareMetal\GFX\Services\Encoders;

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Enums\ScanDirection;

/**
 * Decodes palette-less 1bpp monochrome frames (MONO_VERTICAL_PAGE or
 * MONO_HORIZONTAL) into RGBA8888: lit bits become opaque white, unlit bits
 * opaque black.
 *
 * The bit unpacking is the exact inverse of VerticalPagePacker /
 * MonoHorizontalPacker, including bit-order and scan-direction handling, so
 * a pack → transcode round trip reproduces the logical pixel grid.
 */
class MonoToRgbaEncoder extends RgbaEncoder
{
    protected function supportsSource(FormatSpec $source): bool
    {
        $mono = ($source->pixel_format === PixelFormat::MONO_VERTICAL_PAGE)
            || ($source->pixel_format === PixelFormat::MONO_HORIZONTAL);

        return $mono && ($source->bit_depth === BitDepth::B1) && is_null($source->palette);
    }

    protected function decodeToRgbaWords(DumpedBuffer $dump): array
    {
        [$width, $height] = $this->dimensions($dump);

        $grid = ($dump->metadata->pixel_format === PixelFormat::MONO_VERTICAL_PAGE)
            ? $this->unpackVerticalPages($dump->raw_data, $dump->metadata, $width, $height)
            : $this->unpackHorizontalRows($dump->raw_data, $dump->metadata, $width, $height);

        $words = [];

        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $words[] = ($grid[$row][$col] === 1) ? 0xFFFFFFFF : 0x000000FF;
            }
        }

        return $words;
    }

    /**
     * @param  array<int, int>  $bytes
     * @return array<int, array<int, int>>
     */
    protected function unpackVerticalPages(array $bytes, FormatSpec $spec, int $width, int $height): array
    {
        $msb_first = ($spec->bit_order === BitOrder::MSB_FIRST);
        $flip_rows = ($spec->scan_direction === ScanDirection::BOTTOM_TO_TOP);

        $grid = array_fill(0, $height, array_fill(0, $width, 0));

        for ($row = 0; $row < $height; $row++) {
            $page = intdiv($row, 8);
            $offset = $row % 8;
            $bit = $msb_first ? (7 - $offset) : $offset;
            $target_row = $flip_rows ? ($height - 1 - $row) : $row;

            for ($col = 0; $col < $width; $col++) {
                $byte = $bytes[($page * $width) + $col] ?? 0;
                $grid[$target_row][$col] = ($byte >> $bit) & 1;
            }
        }

        return $grid;
    }

    /**
     * @param  array<int, int>  $bytes
     * @return array<int, array<int, int>>
     */
    protected function unpackHorizontalRows(array $bytes, FormatSpec $spec, int $width, int $height): array
    {
        $msb_first = ($spec->bit_order !== BitOrder::LSB_FIRST);
        $bytes_per_row = intdiv($width + 7, 8);

        $grid = array_fill(0, $height, array_fill(0, $width, 0));

        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $byte = $bytes[($row * $bytes_per_row) + intdiv($col, 8)] ?? 0;
                $bit = $msb_first ? (7 - ($col % 8)) : ($col % 8);
                $grid[$row][$col] = ($byte >> $bit) & 1;
            }
        }

        return $grid;
    }
}
