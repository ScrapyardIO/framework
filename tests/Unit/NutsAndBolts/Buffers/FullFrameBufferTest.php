<?php

use ScrapyardIO\NutsAndBolts\Buffers\FullFrameBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\Endianness;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\RenderType;

function fullFrameBuffer(int $width = 2, int $height = 2, ?FormatSpec $spec = null): FullFrameBuffer
{
    return new FullFrameBuffer($width, $height, $spec ?? new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8));
}

it('dumps a single full-frame update', function () {
    $updates = fullFrameBuffer()->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0])->toBeInstanceOf(DumpedBuffer::class)
        ->and($updates[0]->render_type)->toBe(RenderType::FULL);
});

it('carries its format spec as the update metadata', function () {
    $spec = new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8);

    $updates = fullFrameBuffer(2, 2, $spec)->dump();

    expect($updates[0]->metadata)->toBe($spec);
});

it('places the frame at the surface origin', function () {
    $updates = fullFrameBuffer()->dump();

    expect($updates[0]->origin_x)->toBe(0)
        ->and($updates[0]->origin_y)->toBe(0);
});

it('emits the whole grid as a flat row-major byte stream', function () {
    $buffer = fullFrameBuffer(3, 2);
    $buffer->setPixel(2, 0, 1)->setPixel(0, 1, 2);

    expect($buffer->dump()[0]->raw_data)->toBe([0, 0, 1, 2, 0, 0]);
});

it('splits multi-byte pixels high byte first for a 16-bit spec', function () {
    $spec = new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::MSB);
    $buffer = fullFrameBuffer(2, 1, $spec);
    $buffer->setPixel(0, 0, 0xF800)->setPixel(1, 0, 0x07E0);

    expect($buffer->dump()[0]->raw_data)->toBe([0xF8, 0x00, 0x07, 0xE0]);
});

it('flushes the frame then clears the surface', function () {
    $buffer = fullFrameBuffer(2, 1);
    $buffer->setPixel(0, 0, 9);

    $updates = $buffer->flush();

    expect($updates[0]->raw_data)->toBe([9, 0])
        ->and($buffer->dump()[0]->raw_data)->toBe([0, 0]);
});
