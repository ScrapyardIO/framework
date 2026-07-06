<?php

namespace DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Framebuffers;

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\PixelPacker;

/**
 * A stand-in packer for registry tests: emits a recognizable sentinel byte
 * stream instead of real packing.
 */
class FakePlanarPacker implements PixelPacker
{
    public function pack(array $grid, FormatSpec $spec, int $width, int $height): array
    {
        return [0xAA, 0xBB, $width, $height];
    }
}
