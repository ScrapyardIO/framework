<?php

namespace BareMetal\Contracts\Framebuffers;

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;

/**
 * Packs a logical pixel grid into the wire layout a FormatSpec describes.
 *
 * Packers are pure and stateless: the same grid and spec always produce the
 * same byte stream, so each panel family's packing rules can be implemented
 * and golden-byte tested in isolation from any buffer.
 */
interface PixelPacker
{
    /**
     * @param  array<int, array<int, int>>  $grid  Row-major logical pixel values; missing cells read as 0.
     * @return array<int, int> The packed byte stream.
     */
    public function pack(array $grid, FormatSpec $spec, int $width, int $height): array;
}
