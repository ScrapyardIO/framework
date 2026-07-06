<?php

use BareMetal\Contracts\Framebuffers\DTO\ChannelPalette;
use BareMetal\Contracts\Framebuffers\DTO\ChannelSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\Endianness;
use BareMetal\Contracts\Framebuffers\Enums\PageAxis;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Enums\ScanDirection;
use BareMetal\Contracts\Framebuffers\FramebufferException;
use BareMetal\Framebuffers\FullFramebuffer;
use BareMetal\Framebuffers\FullFramebufferFactory;

test('its defaults match a plain, unconfigured buffer spec', function () {
    $factory = new FullFramebufferFactory(128, 64);

    expect($factory->pixel_format)->toBeNull()
        ->and($factory->bit_depth)->toBeNull()
        ->and($factory->scan_direction)->toBe(ScanDirection::TOP_TO_BOTTOM)
        ->and($factory->bit_order)->toBeNull()
        ->and($factory->endianness)->toBeNull()
        ->and($factory->page_axis)->toBeNull()
        ->and($factory->palette)->toBeNull();
});

test('every setter is fluent and sets the expected property', function () {
    $factory = new FullFramebufferFactory(128, 64);
    $palette = new ChannelPalette(new ChannelSpec(1));

    $result = $factory
        ->pixelFormat(PixelFormat::ROW_MAJOR)
        ->bitDepth(BitDepth::B16)
        ->scanDirection(ScanDirection::BOTTOM_TO_TOP)
        ->bitOrder(BitOrder::MSB_FIRST)
        ->endianness(Endianness::MSB)
        ->pageAxis(PageAxis::VERTICAL)
        ->palette($palette);

    expect($result)->toBe($factory)
        ->and($factory->pixel_format)->toBe(PixelFormat::ROW_MAJOR)
        ->and($factory->bit_depth)->toBe(BitDepth::B16)
        ->and($factory->scan_direction)->toBe(ScanDirection::BOTTOM_TO_TOP)
        ->and($factory->bit_order)->toBe(BitOrder::MSB_FIRST)
        ->and($factory->endianness)->toBe(Endianness::MSB)
        ->and($factory->page_axis)->toBe(PageAxis::VERTICAL)
        ->and($factory->palette)->toBe($palette);
});

test('build() produces a buffer carrying the configured FormatSpec', function () {
    $buffer = FullFramebuffer::size(128, 64)
        ->pixelFormat(PixelFormat::ROW_MAJOR)
        ->bitDepth(BitDepth::B16)
        ->endianness(Endianness::MSB)
        ->build();

    expect($buffer)->toBeInstanceOf(FullFramebuffer::class)
        ->and($buffer->viewportWidth())->toBe(128)
        ->and($buffer->viewportHeight())->toBe(64)
        ->and($buffer->formatSpec()->pixel_format)->toBe(PixelFormat::ROW_MAJOR)
        ->and($buffer->formatSpec()->bit_depth)->toBe(BitDepth::B16)
        ->and($buffer->formatSpec()->endianness)->toBe(Endianness::MSB);
});

test('build() throws when no pixel format has been set', function () {
    (new FullFramebufferFactory(8, 8))->build();
})->throws(FramebufferException::class, 'Missing pixel format.');

test('build() throws when no bit depth has been set', function () {
    (new FullFramebufferFactory(8, 8))
        ->pixelFormat(PixelFormat::ROW_MAJOR)
        ->build();
})->throws(FramebufferException::class, 'Missing bit depth.');

test('paged mono packing rejects bit depths other than 1', function () {
    (new FullFramebufferFactory(8, 8))
        ->pixelFormat(PixelFormat::MONO_VERTICAL_PAGE)
        ->bitDepth(BitDepth::B16)
        ->bitOrder(BitOrder::LSB_FIRST)
        ->build();
})->throws(FramebufferException::class, 'mono_vertical_page packing requires 1-bit depth, got 16.');

test('mono packing requires a bit order', function () {
    (new FullFramebufferFactory(8, 8))
        ->pixelFormat(PixelFormat::MONO_HORIZONTAL)
        ->bitDepth(BitDepth::B1)
        ->build();
})->throws(FramebufferException::class, 'mono_horizontal packing requires a bit order.');

test('vertical page packing rejects a horizontal page axis', function () {
    (new FullFramebufferFactory(8, 8))
        ->pixelFormat(PixelFormat::MONO_VERTICAL_PAGE)
        ->bitDepth(BitDepth::B1)
        ->bitOrder(BitOrder::LSB_FIRST)
        ->pageAxis(PageAxis::HORIZONTAL)
        ->build();
})->throws(FramebufferException::class, 'mono_vertical_page packing cannot use a horizontal page axis.');

test('multi-byte row-major packing requires an endianness', function () {
    (new FullFramebufferFactory(8, 8))
        ->pixelFormat(PixelFormat::ROW_MAJOR)
        ->bitDepth(BitDepth::B16)
        ->build();
})->throws(FramebufferException::class, 'row_major packing with 16-bit pixels requires an endianness.');

test('single-byte row-major packing builds without an endianness', function () {
    $buffer = (new FullFramebufferFactory(8, 8))
        ->pixelFormat(PixelFormat::ROW_MAJOR)
        ->bitDepth(BitDepth::B8)
        ->build();

    expect($buffer)->toBeInstanceOf(FullFramebuffer::class);
});

test('planar packing requires a channel palette', function () {
    (new FullFramebufferFactory(8, 8))
        ->pixelFormat(PixelFormat::PLANAR)
        ->bitDepth(BitDepth::B1)
        ->build();
})->throws(FramebufferException::class, 'planar packing requires a channel palette.');

test('planar packing builds once a palette is supplied', function () {
    $buffer = (new FullFramebufferFactory(8, 8))
        ->pixelFormat(PixelFormat::PLANAR)
        ->bitDepth(BitDepth::B1)
        ->palette(new ChannelPalette(new ChannelSpec(1), new ChannelSpec(2, inverted: true)))
        ->build();

    expect($buffer)->toBeInstanceOf(FullFramebuffer::class)
        ->and($buffer->formatSpec()->palette->count())->toBe(2);
});
