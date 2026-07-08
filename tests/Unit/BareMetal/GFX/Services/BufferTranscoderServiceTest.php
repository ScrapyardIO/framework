<?php

use BareMetal\Contracts\Displays\ElectronicInk\eInkColor;
use BareMetal\Contracts\Framebuffers\DTO\ChannelPalette;
use BareMetal\Contracts\Framebuffers\DTO\ChannelSpec;
use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\Endianness;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Enums\RenderType;
use BareMetal\Contracts\GFX\TranscoderException;
use BareMetal\GFX\Services\BufferTranscoderService;
use DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\GFX\MagentaEncoder;

function rgbaDisplaySpec(): FormatSpec
{
    return new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B32, endianness: Endianness::MSB);
}

function rgbaTranscoder(): BufferTranscoderService
{
    return new BufferTranscoderService(rgbaDisplaySpec());
}

/**
 * Split a flat RGBA byte stream back into per-pixel 4-byte groups.
 */
function rgbaPixels(DumpedBuffer $frame): array
{
    return array_chunk($frame->raw_data, 4);
}

afterEach(fn () => BufferTranscoderService::reset());

test('a dump whose spec already matches the display passes through untouched', function () {
    $dump = new DumpedBuffer(RenderType::FULL, rgbaDisplaySpec(), [0xFF, 0x00, 0x00, 0xFF], width: 1, height: 1);

    expect(rgbaTranscoder()->transcode($dump))->toBe($dump);
});

test('spec matching is by value, not identity', function () {
    // Separately constructed but identical specs still take the fast path.
    $dump = new DumpedBuffer(
        RenderType::FULL,
        new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B32, endianness: Endianness::MSB),
        [0x00, 0xFF, 0x00, 0xFF],
        width: 1,
        height: 1,
    );

    expect(rgbaTranscoder()->transcode($dump))->toBe($dump);
});

test('MONO_VERTICAL_PAGE B1 becomes white-on-black RGBA8888', function () {
    // 2x8 page, LSB first: 0x01 lights only (0, 0).
    $dump = new DumpedBuffer(
        RenderType::PARTIAL,
        new FormatSpec(PixelFormat::MONO_VERTICAL_PAGE, BitDepth::B1, bit_order: BitOrder::LSB_FIRST),
        [0x01, 0x00],
        origin_y: 8,
        width: 2,
        height: 8,
    );

    $frame = rgbaTranscoder()->transcode($dump);
    $pixels = rgbaPixels($frame);

    expect($frame->metadata->pixel_format)->toBe(PixelFormat::ROW_MAJOR)
        ->and($frame->metadata->bit_depth)->toBe(BitDepth::B32)
        ->and($frame->render_type)->toBe(RenderType::PARTIAL)
        ->and($frame->origin_y)->toBe(8)
        ->and($frame->width)->toBe(2)
        ->and($frame->height)->toBe(8)
        ->and($pixels)->toHaveCount(16)
        ->and($pixels[0])->toBe([0xFF, 0xFF, 0xFF, 0xFF])
        ->and($pixels[1])->toBe([0x00, 0x00, 0x00, 0xFF])
        ->and(array_unique(array_slice($pixels, 1), SORT_REGULAR))->toBe([[0x00, 0x00, 0x00, 0xFF]]);
});

test('MONO_VERTICAL_PAGE honours MSB_FIRST bit order when decoding', function () {
    // MSB first puts the top row in bit 7.
    $dump = new DumpedBuffer(
        RenderType::PARTIAL,
        new FormatSpec(PixelFormat::MONO_VERTICAL_PAGE, BitDepth::B1, bit_order: BitOrder::MSB_FIRST),
        [0x80],
        width: 1,
        height: 8,
    );

    $pixels = rgbaPixels(rgbaTranscoder()->transcode($dump));

    expect($pixels[0])->toBe([0xFF, 0xFF, 0xFF, 0xFF])
        ->and($pixels[7])->toBe([0x00, 0x00, 0x00, 0xFF]);
});

test('MONO_HORIZONTAL B1 decodes MSB-first rows into RGBA8888', function () {
    // One 8-pixel row: 0x81 lights the first and last columns.
    $dump = new DumpedBuffer(
        RenderType::FULL,
        new FormatSpec(PixelFormat::MONO_HORIZONTAL, BitDepth::B1, bit_order: BitOrder::MSB_FIRST),
        [0x81],
        width: 8,
        height: 1,
    );

    $pixels = rgbaPixels(rgbaTranscoder()->transcode($dump));

    expect($pixels[0])->toBe([0xFF, 0xFF, 0xFF, 0xFF])
        ->and($pixels[1])->toBe([0x00, 0x00, 0x00, 0xFF])
        ->and($pixels[7])->toBe([0xFF, 0xFF, 0xFF, 0xFF]);
});

test('ROW_MAJOR RGB565 expands to RGBA8888 with bit replication', function () {
    // Pure red, pure green, pure blue, big-endian RGB565 (the ST77xx wire order).
    $dump = new DumpedBuffer(
        RenderType::FULL,
        new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::MSB),
        [0xF8, 0x00, 0x07, 0xE0, 0x00, 0x1F],
        width: 3,
        height: 1,
    );

    $pixels = rgbaPixels(rgbaTranscoder()->transcode($dump));

    expect($pixels)->toBe([
        [0xFF, 0x00, 0x00, 0xFF],
        [0x00, 0xFF, 0x00, 0xFF],
        [0x00, 0x00, 0xFF, 0xFF],
    ]);
});

test('ROW_MAJOR RGB565 respects LSB source endianness', function () {
    $dump = new DumpedBuffer(
        RenderType::FULL,
        new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::LSB),
        [0x00, 0xF8],
        width: 1,
        height: 1,
    );

    expect(rgbaPixels(rgbaTranscoder()->transcode($dump))[0])->toBe([0xFF, 0x00, 0x00, 0xFF]);
});

test('an LSB display spec flips the emitted RGBA byte order', function () {
    $transcoder = new BufferTranscoderService(
        new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B32, endianness: Endianness::LSB)
    );

    $dump = new DumpedBuffer(
        RenderType::FULL,
        new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::MSB),
        [0xF8, 0x00],
        width: 1,
        height: 1,
    );

    // A, B, G, R when the display wants little-endian words.
    expect($transcoder->transcode($dump)->raw_data)->toBe([0xFF, 0x00, 0x00, 0xFF]);
});

test('palette-aware planar mono maps channel bits to real eInk RGBA colors', function () {
    // BWR panel, 8x1: black plane is inverted (SSD1680 sense, 0 = black),
    // red plane is direct. Bit 7 is the leftmost column.
    $palette = new ChannelPalette(
        new ChannelSpec(eInkColor::BLACK->value, inverted: true),
        new ChannelSpec(eInkColor::RED->value),
    );

    $dump = new DumpedBuffer(
        RenderType::FULL,
        new FormatSpec(PixelFormat::PLANAR, BitDepth::B1, bit_order: BitOrder::MSB_FIRST, palette: $palette),
        [0x7F, 0x40], // black plane: col 0 black; red plane: col 1 red
        width: 8,
        height: 1,
    );

    $pixels = rgbaPixels(rgbaTranscoder()->transcode($dump));

    expect($pixels[0])->toBe([0x00, 0x00, 0x00, 0xFF])
        ->and($pixels[1])->toBe([0xFF, 0x00, 0x00, 0xFF])
        ->and($pixels[2])->toBe([0xFF, 0xFF, 0xFF, 0xFF])
        ->and($pixels[7])->toBe([0xFF, 0xFF, 0xFF, 0xFF]);
});

test('a single-channel MONO_HORIZONTAL palette surface decodes through its channel color', function () {
    $palette = new ChannelPalette(new ChannelSpec(eInkColor::BLACK->value));

    $dump = new DumpedBuffer(
        RenderType::FULL,
        new FormatSpec(PixelFormat::MONO_HORIZONTAL, BitDepth::B1, bit_order: BitOrder::MSB_FIRST, palette: $palette),
        [0x80],
        width: 8,
        height: 1,
    );

    $pixels = rgbaPixels(rgbaTranscoder()->transcode($dump));

    expect($pixels[0])->toBe([0x00, 0x00, 0x00, 0xFF])
        ->and($pixels[1])->toBe([0xFF, 0xFF, 0xFF, 0xFF]);
});

test('an unknown conversion raises a typed exception', function () {
    // No built-in converts 8-bit row-major into RGBA.
    $dump = new DumpedBuffer(
        RenderType::FULL,
        new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8),
        [0x2A],
        width: 1,
        height: 1,
    );

    rgbaTranscoder()->transcode($dump);
})->throws(TranscoderException::class, 'No encoder');

test('a mono dump without dimensions raises a typed exception', function () {
    $dump = new DumpedBuffer(
        RenderType::FULL,
        new FormatSpec(PixelFormat::MONO_VERTICAL_PAGE, BitDepth::B1, bit_order: BitOrder::LSB_FIRST),
        [0x01],
    );

    rgbaTranscoder()->transcode($dump);
})->throws(TranscoderException::class, 'width/height');

test('a registered encoder covers a conversion with no built-in', function () {
    BufferTranscoderService::register(MagentaEncoder::class);

    $dump = new DumpedBuffer(
        RenderType::FULL,
        new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8),
        [0x2A],
        width: 1,
        height: 1,
    );

    expect(rgbaTranscoder()->transcode($dump)->raw_data)->toBe([0xFF, 0x00, 0xFF, 0xFF]);
});

test('a registered encoder is consulted before the built-ins', function () {
    BufferTranscoderService::register(MagentaEncoder::class);

    // RGB565 would normally hit Rgb565ToRgbaEncoder; the custom encoder wins.
    $dump = new DumpedBuffer(
        RenderType::FULL,
        new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::MSB),
        [0xF8, 0x00],
        width: 1,
        height: 1,
    );

    expect(rgbaTranscoder()->transcode($dump)->raw_data)->toBe([0xFF, 0x00, 0xFF, 0xFF]);
});

test('reset() restores the built-in defaults', function () {
    BufferTranscoderService::register(MagentaEncoder::class);
    BufferTranscoderService::reset();

    $dump = new DumpedBuffer(
        RenderType::FULL,
        new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::MSB),
        [0xF8, 0x00],
        width: 1,
        height: 1,
    );

    expect(rgbaTranscoder()->transcode($dump)->raw_data)->toBe([0xFF, 0x00, 0x00, 0xFF]);
});

test('registering a class that is not a BufferEncoder throws', function () {
    BufferTranscoderService::register(stdClass::class);
})->throws(TranscoderException::class, 'does not implement');
