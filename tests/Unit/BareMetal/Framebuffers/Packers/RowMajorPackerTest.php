<?php

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\Endianness;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Framebuffers\Packers\RowMajorPacker;

function rowMajorSpec(BitDepth $depth, ?Endianness $endianness = null): FormatSpec
{
    return new FormatSpec(PixelFormat::ROW_MAJOR, $depth, endianness: $endianness);
}

test('packs RGB565 pixels big-endian first — the ST77xx convention', function () {
    $grid = [
        [0xF800, 0x07E0],
        [0x001F, 0xFFFF],
    ];

    $bytes = (new RowMajorPacker)->pack($grid, rowMajorSpec(BitDepth::B16, Endianness::MSB), 2, 2);

    expect($bytes)->toBe([
        0xF8, 0x00,  0x07, 0xE0,
        0x00, 0x1F,  0xFF, 0xFF,
    ]);
});

test('packs RGB565 pixels little-endian when the spec says LSB', function () {
    $grid = [
        [0xF800, 0x07E0],
        [0x001F, 0xFFFF],
    ];

    $bytes = (new RowMajorPacker)->pack($grid, rowMajorSpec(BitDepth::B16, Endianness::LSB), 2, 2);

    expect($bytes)->toBe([
        0x00, 0xF8,  0xE0, 0x07,
        0x1F, 0x00,  0xFF, 0xFF,
    ]);
});

test('8-bit pixels emit one byte each, endianness moot', function () {
    $grid = [[0x12, 0x34, 0xFF]];

    $bytes = (new RowMajorPacker)->pack($grid, rowMajorSpec(BitDepth::B8), 3, 1);

    expect($bytes)->toBe([0x12, 0x34, 0xFF]);
});

test('18-bit pixels are sliced into three bytes', function () {
    $grid = [[0x03FFFF]];

    $bytes = (new RowMajorPacker)->pack($grid, rowMajorSpec(BitDepth::B18, Endianness::MSB), 1, 1);

    expect($bytes)->toBe([0x03, 0xFF, 0xFF]);
});

test('missing cells read as 0 and the output length is always width * height * bytes-per-pixel', function () {
    $bytes = (new RowMajorPacker)->pack([], rowMajorSpec(BitDepth::B16, Endianness::MSB), 2, 2);

    expect($bytes)->toBe([0, 0, 0, 0, 0, 0, 0, 0]);
});
