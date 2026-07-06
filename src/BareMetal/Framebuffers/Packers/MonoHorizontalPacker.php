<?php

namespace BareMetal\Framebuffers\Packers;

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\PixelPacker;

/**
 * Packs a row-major logical grid into 1bpp horizontal bytes: 8 adjacent
 * columns per byte, emitted row-major. BitOrder picks which end of the byte
 * the leftmost column lands on (MSB_FIRST = bit 7, the SSD1680/IL3820/TFT
 * mono convention). Rows are padded to a byte boundary with 0 bits.
 *
 * Bit-polarity inversion (the SSD1680 black-RAM sense) is a per-channel
 * concern carried by ChannelSpec and belongs to the planar packing family,
 * not here.
 */
final class MonoHorizontalPacker implements PixelPacker
{
    public function pack(array $grid, FormatSpec $spec, int $width, int $height): array
    {
        $msb_first = ($spec->bit_order !== BitOrder::LSB_FIRST);

        $bytes = [];

        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col += 8) {
                $byte = 0;

                for ($bit = 0; $bit < 8; $bit++) {
                    $x = $col + $bit;
                    $on = ($x < $width) ? (($grid[$row][$x] ?? 0) & 1) : 0;

                    if ($on === 1) {
                        $position = $msb_first ? (7 - $bit) : $bit;
                        $byte |= (1 << $position);
                    }
                }

                $bytes[] = $byte;
            }
        }

        return $bytes;
    }
}
