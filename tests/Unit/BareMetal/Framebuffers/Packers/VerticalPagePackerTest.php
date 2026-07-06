<?php

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Enums\ScanDirection;
use BareMetal\Framebuffers\Packers\VerticalPagePacker;

function verticalPageSpec(BitOrder $bit_order, ScanDirection $scan = ScanDirection::TOP_TO_BOTTOM): FormatSpec
{
    return new FormatSpec(PixelFormat::MONO_VERTICAL_PAGE, BitDepth::B1, $scan, $bit_order);
}

/**
 * @param  array<int, array{0: int, 1: int}>  $on  [x, y] cells set to 1
 * @return array<int, array<int, int>>
 */
function verticalPageGrid(int $width, int $height, array $on): array
{
    $grid = array_fill(0, $height, array_fill(0, $width, 0));

    foreach ($on as [$x, $y]) {
        $grid[$y][$x] = 1;
    }

    return $grid;
}

test('the top row lands in bit 0 with LSB_FIRST — the SSD1306/SH1106 convention', function () {
    $grid = verticalPageGrid(3, 8, [[0, 0]]);

    $bytes = (new VerticalPagePacker)->pack($grid, verticalPageSpec(BitOrder::LSB_FIRST), 3, 8);

    expect($bytes)->toBe([0x01, 0x00, 0x00]);
});

test('the top row lands in bit 7 with MSB_FIRST', function () {
    $grid = verticalPageGrid(3, 8, [[0, 0]]);

    $bytes = (new VerticalPagePacker)->pack($grid, verticalPageSpec(BitOrder::MSB_FIRST), 3, 8);

    expect($bytes)->toBe([0x80, 0x00, 0x00]);
});

test('a fully lit column packs to 0xFF in its page byte', function () {
    $on = [];
    for ($y = 0; $y < 8; $y++) {
        $on[] = [1, $y];
    }
    $grid = verticalPageGrid(3, 8, $on);

    $bytes = (new VerticalPagePacker)->pack($grid, verticalPageSpec(BitOrder::LSB_FIRST), 3, 8);

    expect($bytes)->toBe([0x00, 0xFF, 0x00]);
});

test('heights that are not a multiple of 8 pad out a final page', function () {
    $grid = verticalPageGrid(2, 10, [[0, 9]]);

    $bytes = (new VerticalPagePacker)->pack($grid, verticalPageSpec(BitOrder::LSB_FIRST), 2, 10);

    // 2 pages x 2 columns; (0, 9) is page 1, offset 1 -> bit 1.
    expect($bytes)->toBe([0x00, 0x00, 0x02, 0x00]);
});

test('BOTTOM_TO_TOP flips the surface vertically before packing', function () {
    $grid = verticalPageGrid(1, 8, [[0, 7]]);

    $bytes = (new VerticalPagePacker)
        ->pack($grid, verticalPageSpec(BitOrder::LSB_FIRST, ScanDirection::BOTTOM_TO_TOP), 1, 8);

    // The bottom row becomes the top row, so it lands in bit 0.
    expect($bytes)->toBe([0x01]);
});
