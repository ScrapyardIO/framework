<?php

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Framebuffers\Packers\MonoHorizontalPacker;

function monoHorizontalSpec(BitOrder $bit_order): FormatSpec
{
    return new FormatSpec(PixelFormat::MONO_HORIZONTAL, BitDepth::B1, bit_order: $bit_order);
}

/**
 * @param  array<int, array{0: int, 1: int}>  $on  [x, y] cells set to 1
 * @return array<int, array<int, int>>
 */
function monoHorizontalGrid(int $width, int $height, array $on): array
{
    $grid = array_fill(0, $height, array_fill(0, $width, 0));

    foreach ($on as [$x, $y]) {
        $grid[$y][$x] = 1;
    }

    return $grid;
}

test('the leftmost column lands in bit 7 with MSB_FIRST — the SSD1680/IL3820 convention', function () {
    $grid = monoHorizontalGrid(10, 2, [[0, 0], [9, 0]]);

    $bytes = (new MonoHorizontalPacker)->pack($grid, monoHorizontalSpec(BitOrder::MSB_FIRST), 10, 2);

    // Row 0: x=0 -> byte 0 bit 7; x=9 -> byte 1 bit 6. Row 1 is empty but still padded.
    expect($bytes)->toBe([0x80, 0x40, 0x00, 0x00]);
});

test('the leftmost column lands in bit 0 with LSB_FIRST', function () {
    $grid = monoHorizontalGrid(10, 2, [[0, 0], [9, 0]]);

    $bytes = (new MonoHorizontalPacker)->pack($grid, monoHorizontalSpec(BitOrder::LSB_FIRST), 10, 2);

    expect($bytes)->toBe([0x01, 0x02, 0x00, 0x00]);
});

test('rows are padded to a byte boundary with 0 bits', function () {
    $grid = monoHorizontalGrid(10, 1, []);

    $bytes = (new MonoHorizontalPacker)->pack($grid, monoHorizontalSpec(BitOrder::MSB_FIRST), 10, 1);

    expect($bytes)->toHaveCount(2)
        ->and($bytes)->toBe([0x00, 0x00]);
});

test('only the low bit of each cell value counts', function () {
    $grid = [[2, 3]];

    $bytes = (new MonoHorizontalPacker)->pack($grid, monoHorizontalSpec(BitOrder::MSB_FIRST), 2, 1);

    // 2 & 1 = 0 (off), 3 & 1 = 1 (on, bit 6).
    expect($bytes)->toBe([0x40]);
});
