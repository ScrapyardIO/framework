<?php

use ScrapyardIO\NutsAndBolts\Buffers\Factory\FullFrameBufferFactory;
use ScrapyardIO\NutsAndBolts\Buffers\FullFrameBuffer;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\PageAxis;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\ScanDirection;

it('exposes the matching factory from size()', function () {
    expect(FullFrameBuffer::size(8, 8))->toBeInstanceOf(FullFrameBufferFactory::class);
});

it('builds a full frame buffer through the fluent factory', function () {
    $buffer = FullFrameBuffer::size(128, 64)
        ->pixelFormat(PixelFormat::MONO_VERTICAL_PAGE)
        ->bitDepth(BitDepth::B1)
        ->build();

    expect($buffer)->toBeInstanceOf(FullFrameBuffer::class)
        ->and($buffer->width)->toBe(128)
        ->and($buffer->height)->toBe(64);
});

it('carries the specced format facts into the built buffer', function () {
    $spec = FullFrameBuffer::size(2, 2)
        ->pixelFormat(PixelFormat::MONO_VERTICAL_PAGE)
        ->bitDepth(BitDepth::B1)
        ->pageAxis(PageAxis::VERTICAL)
        ->build()
        ->dump()[0]->metadata;

    expect($spec->pixel_format)->toBe(PixelFormat::MONO_VERTICAL_PAGE)
        ->and($spec->bit_depth)->toBe(BitDepth::B1)
        ->and($spec->page_axis)->toBe(PageAxis::VERTICAL);
});

it('defaults the optional spec facts when left unset', function () {
    $spec = FullFrameBuffer::size(2, 2)
        ->pixelFormat(PixelFormat::ROW_MAJOR)
        ->bitDepth(BitDepth::B8)
        ->build()
        ->dump()[0]->metadata;

    expect($spec->scan_direction)->toBe(ScanDirection::TOP_TO_BOTTOM)
        ->and($spec->bit_order)->toBeNull()
        ->and($spec->endianness)->toBeNull()
        ->and($spec->page_axis)->toBeNull();
});

it('refuses to build without a pixel format', function () {
    FullFrameBuffer::size(2, 2)->bitDepth(BitDepth::B1)->build();
})->throws(Exception::class, 'Missing pixel format.');

it('refuses to build without a bit depth', function () {
    FullFrameBuffer::size(2, 2)->pixelFormat(PixelFormat::ROW_MAJOR)->build();
})->throws(Exception::class, 'Missing bit depth.');
