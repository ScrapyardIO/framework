<?php

namespace DeptOfScrapyardRobotics\Tests\Framebuffers;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\Endianness;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\Framebuffers\Packers\RowMajorPacker;
use PHPUnit\Framework\TestCase;

class RowMajorPackerTest extends TestCase
{
    public function testColor12PacksTwoPixelsIntoThreeBytes(): void
    {
        $spec = new FormatSpec(
            PixelFormat::ROW_MAJOR,
            BitDepth::B12,
            endianness: Endianness::MSB,
        );

        // red 0xF00 + green 0x0F0 → [0xF0, 0x00, 0xF0]
        $bytes = (new RowMajorPacker)->pack([
            [0x0F00, 0x00F0],
        ], $spec, 2, 1);

        $this->assertSame([0xF0, 0x00, 0xF0], $bytes);
    }

    public function testColor12PadsOddPixelCount(): void
    {
        $spec = new FormatSpec(
            PixelFormat::ROW_MAJOR,
            BitDepth::B12,
            endianness: Endianness::MSB,
        );

        $bytes = (new RowMajorPacker)->pack([
            [0x0ABC],
        ], $spec, 1, 1);

        $this->assertSame([0xAB, 0xC0, 0x00], $bytes);
        $this->assertCount(3, $bytes);
    }

    public function testColor16PacksRgb565MsbFirst(): void
    {
        $spec = new FormatSpec(
            PixelFormat::ROW_MAJOR,
            BitDepth::B16,
            endianness: Endianness::MSB,
        );

        // 0xF800 red → [0xF8, 0x00]
        $bytes = (new RowMajorPacker)->pack([
            [0xF800],
        ], $spec, 1, 1);

        $this->assertSame([0xF8, 0x00], $bytes);
    }

    public function testColor18PacksLeftJustifiedRgb666(): void
    {
        $spec = new FormatSpec(
            PixelFormat::ROW_MAJOR,
            BitDepth::B18,
            endianness: Endianness::MSB,
        );

        // max red left-justified 0xFC0000 → [0xFC, 0x00, 0x00]
        $bytes = (new RowMajorPacker)->pack([
            [0xFC0000],
        ], $spec, 1, 1);

        $this->assertSame([0xFC, 0x00, 0x00], $bytes);
    }
}
