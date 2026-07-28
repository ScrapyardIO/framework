<?php

namespace Fabricate\Framebuffers\Packers;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\Endianness;
use Fabricate\Contracts\Framebuffers\PixelPacker;
use Fabricate\Framebuffers\FormatSpec;

/**
 * Flattens a row-major logical grid into a panel-native byte stream.
 *
 * The grid already holds panel-native colour words:
 * - B16 RGB565 → 2 bytes/pixel (endianness-aware)
 * - B18 RGB666 left-justified → 3 bytes/pixel (`RRRRRRxx GGGGGGxx BBBBBBxx`)
 * - B12 RGB444 → 2 pixels → 3 bytes (ST77xx nibble packing)
 *
 * Missing cells default to 0.
 */
final class RowMajorPacker implements PixelPacker
{
    public function pack(array $grid, FormatSpec $spec, int $width, int $height): array
    {
        if ($spec->bit_depth === BitDepth::B12) {
            return $this->packRgb444Pairs($grid, $width, $height);
        }

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

    /**
     * ST77xx COLOR12 wire format: two RGB444 pixels → three SPI bytes.
     *
     * Pixel word is `0x0RGB` (4 bits/channel). Byte layout:
     * `R1 G1 | B1 R2 | G2 B2`.
     *
     * @param  array<int, array<int, int>>  $grid
     * @return array<int, int>
     */
    protected function packRgb444Pairs(array $grid, int $width, int $height): array
    {
        $bytes = [];
        $pending = null;

        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $pixel = ($grid[$row][$col] ?? 0) & 0x0FFF;

                if (is_null($pending)) {
                    $pending = $pixel;

                    continue;
                }

                array_push($bytes, ...$this->rgb444PairBytes($pending, $pixel));
                $pending = null;
            }
        }

        if (! is_null($pending)) {
            array_push($bytes, ...$this->rgb444PairBytes($pending, 0));
        }

        return $bytes;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    protected function rgb444PairBytes(int $first, int $second): array
    {
        $r1 = ($first >> 8) & 0xF;
        $g1 = ($first >> 4) & 0xF;
        $b1 = $first & 0xF;
        $r2 = ($second >> 8) & 0xF;
        $g2 = ($second >> 4) & 0xF;
        $b2 = $second & 0xF;

        return [
            ($r1 << 4) | $g1,
            ($b1 << 4) | $r2,
            ($g2 << 4) | $b2,
        ];
    }
}
