<?php

namespace BareMetal\Framebuffers\Packers;

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\Endianness;
use BareMetal\Contracts\Framebuffers\PixelPacker;

/**
 * Flattens a row-major logical grid into a byte stream, slicing each pixel
 * word into bit_depth-many bytes.
 *
 * The grid already holds panel-native colour words (e.g. RGB565), so this is
 * pure byte-slicing: bit_depth fixes the byte width (B16 = 2, B18 = 3) and
 * endianness picks which end leads (MSB_FIRST is the ST77xx/TFT convention).
 * Missing cells default to 0, so the output is always exactly
 * width · height · bytes_per_pixel long.
 */
final class RowMajorPacker implements PixelPacker
{
    public function pack(array $grid, FormatSpec $spec, int $width, int $height): array
    {
        $bytes_per_pixel = intdiv($spec->bit_depth->value + 7, 8);
        $msb_first = ($spec->endianness !== Endianness::LSB);

        $bytes = [];

        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $pixel = $grid[$row][$col] ?? 0;

                for ($i = 0; $i < $bytes_per_pixel; $i++) {
                    $shift = $msb_first ? (($bytes_per_pixel - 1 - $i) * 8) : ($i * 8);
                    $bytes[] = ($pixel >> $shift) & 0xFF;
                }
            }
        }

        return $bytes;
    }
}
