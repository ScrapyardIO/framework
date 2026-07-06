<?php

namespace BareMetal\Framebuffers\Packers;

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\ScanDirection;
use BareMetal\Contracts\Framebuffers\PixelPacker;

/**
 * Packs a row-major logical grid into vertical-page bytes: 8 stacked rows per
 * byte, emitted page-major. BitOrder picks which end of the byte the top row
 * lands on (LSB_FIRST = bit 0, the SSD1306/SH1106 convention); BOTTOM_TO_TOP
 * flips the surface vertically before packing.
 */
final class VerticalPagePacker implements PixelPacker
{
    public function pack(array $grid, FormatSpec $spec, int $width, int $height): array
    {
        $pages = intdiv($height + 7, 8);

        $msb_first = ($spec->bit_order === BitOrder::MSB_FIRST);
        $flip_rows = ($spec->scan_direction === ScanDirection::BOTTOM_TO_TOP);

        $bytes = [];

        for ($page = 0; $page < $pages; $page++) {
            for ($col = 0; $col < $width; $col++) {
                $byte = 0;

                for ($offset = 0; $offset < 8; $offset++) {
                    $row = ($page * 8) + $offset;

                    if ($row >= $height) {
                        continue;
                    }

                    $source_row = $flip_rows ? ($height - 1 - $row) : $row;

                    if (! empty($grid[$source_row][$col])) {
                        $bit = $msb_first ? (7 - $offset) : $offset;
                        $byte |= (1 << $bit);
                    }
                }

                $bytes[] = $byte;
            }
        }

        return $bytes;
    }
}
