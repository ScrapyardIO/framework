<?php

namespace BareMetal\GFX\Services\Encoders;

use BareMetal\Contracts\Displays\ElectronicInk\eInkColor;
use BareMetal\Contracts\Framebuffers\DTO\ChannelSpec;
use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;

/**
 * Decodes palette-aware 1bpp frames (multi-plane ePaper) into RGBA8888.
 *
 * The payload is one MONO_HORIZONTAL-packed plane per palette channel,
 * concatenated in palette order (PLANAR), or a single such plane for a
 * one-channel MONO_HORIZONTAL surface. A pixel takes the real RGBA colour of
 * the first channel that claims it — honouring each ChannelSpec's inverted
 * bit polarity (the SSD1680 black-RAM sense) — and falls back to paper white
 * when no channel does.
 */
class PaletteMonoToRgbaEncoder extends RgbaEncoder
{
    protected function supportsSource(FormatSpec $source): bool
    {
        $planar_mono = ($source->pixel_format === PixelFormat::PLANAR)
            || ($source->pixel_format === PixelFormat::MONO_HORIZONTAL);

        return $planar_mono && ($source->bit_depth === BitDepth::B1) && ! is_null($source->palette);
    }

    protected function decodeToRgbaWords(DumpedBuffer $dump): array
    {
        [$width, $height] = $this->dimensions($dump);

        $msb_first = ($dump->metadata->bit_order !== BitOrder::LSB_FIRST);
        $bytes_per_row = intdiv($width + 7, 8);
        $plane_size = $bytes_per_row * $height;
        $channels = $dump->metadata->palette->channels;

        $words = [];

        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $byte_offset = ($row * $bytes_per_row) + intdiv($col, 8);
                $bit = $msb_first ? (7 - ($col % 8)) : ($col % 8);

                $words[] = $this->claimColor($dump->raw_data, $channels, $plane_size, $byte_offset, $bit);
            }
        }

        return $words;
    }

    /**
     * @param  array<int, int>  $bytes
     * @param  list<ChannelSpec>  $channels
     */
    protected function claimColor(array $bytes, array $channels, int $plane_size, int $byte_offset, int $bit): int
    {
        foreach ($channels as $plane => $channel) {
            $byte = $bytes[($plane * $plane_size) + $byte_offset] ?? 0;
            $lit = (($byte >> $bit) & 1) === ($channel->inverted ? 0 : 1);

            if ($lit) {
                return $this->rgbaFor($channel->color);
            }
        }

        // No plane claimed the pixel: ePaper background paper white.
        return 0xFFFFFFFF;
    }

    protected function rgbaFor(int $color): int
    {
        return match (eInkColor::tryFrom($color)) {
            eInkColor::BLACK => 0x000000FF,
            eInkColor::WHITE => 0xFFFFFFFF,
            eInkColor::RED => 0xFF0000FF,
            eInkColor::YELLOW => 0xFFFF00FF,
            eInkColor::BLUE => 0x0000FFFF,
            eInkColor::GREEN => 0x00FF00FF,
            null => 0xFFFFFFFF,
        };
    }
}
