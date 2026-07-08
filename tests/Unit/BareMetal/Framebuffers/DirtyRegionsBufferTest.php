<?php

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\Endianness;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Enums\RenderType;
use BareMetal\Framebuffers\DirtyRegionsBuffer;
use BareMetal\Framebuffers\DirtyRegionsBufferFactory;

function dirtyBuffer(int $width = 8, int $height = 8, ?FormatSpec $spec = null): DirtyRegionsBuffer
{
    return new DirtyRegionsBuffer($width, $height, $spec ?? new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8));
}

test('size() returns the DirtyRegionsBufferFactory carrying the dimensions', function () {
    $factory = DirtyRegionsBuffer::size(160, 128);

    expect($factory)->toBeInstanceOf(DirtyRegionsBufferFactory::class)
        ->and($factory->width)->toBe(160)
        ->and($factory->height)->toBe(128);
});

it('emits nothing when no writes happened', function () {
    expect(dirtyBuffer()->dump())->toBe([]);
});

it('ships a single dirty rectangle as one partial update', function () {
    $buffer = dirtyBuffer(4, 4);
    $buffer->setSegment(1, 1, 2, 2, 9);

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->render_type)->toBe(RenderType::PARTIAL)
        ->and($updates[0]->origin_x)->toBe(1)
        ->and($updates[0]->origin_y)->toBe(1)
        ->and($updates[0]->width)->toBe(2)
        ->and($updates[0]->height)->toBe(2)
        ->and($updates[0]->raw_data)->toBe([9, 9, 9, 9]);
});

it('coalesces touching writes into a single region', function () {
    $buffer = dirtyBuffer(8, 4);
    $buffer->setPixel(0, 0, 1)->setPixel(1, 0, 2);

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->origin_x)->toBe(0)
        ->and($updates[0]->width)->toBe(2)
        ->and($updates[0]->height)->toBe(1)
        ->and($updates[0]->raw_data)->toBe([1, 2]);
});

it('coalesces transitively when a write bridges two regions', function () {
    $buffer = dirtyBuffer(16, 4);
    $buffer->setPixel(0, 0, 1)->setPixel(2, 0, 2); // two disjoint regions
    $buffer->setPixel(1, 0, 3); // touches both, so all three merge

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->origin_x)->toBe(0)
        ->and($updates[0]->width)->toBe(3)
        ->and($updates[0]->raw_data)->toBe([1, 3, 2]);
});

it('keeps disjoint writes as separate regions', function () {
    $buffer = dirtyBuffer(8, 4);
    $buffer->setPixel(0, 0, 1)->setPixel(5, 0, 2);

    $updates = $buffer->dump();

    $origins = array_map(fn ($update) => $update->origin_x, $updates);
    sort($origins);

    expect($updates)->toHaveCount(2)
        ->and($origins)->toBe([0, 5]);
});

it('clips setSegment() spills to the surface when marking dirty', function () {
    $buffer = dirtyBuffer(4, 4);
    $buffer->setSegment(2, 2, 4, 4, 7); // spills past both edges

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->origin_x)->toBe(2)
        ->and($updates[0]->origin_y)->toBe(2)
        ->and($updates[0]->width)->toBe(2)
        ->and($updates[0]->height)->toBe(2);
});

it('clears the dirty set after dumping', function () {
    $buffer = dirtyBuffer(4, 4);
    $buffer->setPixel(0, 0, 1);

    $buffer->dump();

    expect($buffer->dump())->toBe([]);
});

it('retains the canvas across dumps so a repaint re-emits the same bytes', function () {
    $buffer = dirtyBuffer(3, 2);
    $buffer->setPixel(1, 0, 5);

    $first = $buffer->dump();
    $again = $buffer->markAllDirty()->dump();

    expect($first[0]->raw_data)->toBe([5])
        ->and($again[0]->raw_data)->toBe([0, 5, 0, 0, 0, 0]);
});

it('marks the whole surface dirty as one region', function () {
    $buffer = dirtyBuffer(3, 2);
    $buffer->markAllDirty();

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->origin_x)->toBe(0)
        ->and($updates[0]->origin_y)->toBe(0)
        ->and($updates[0]->width)->toBe(3)
        ->and($updates[0]->height)->toBe(2);
});

it('splits multi-byte pixels high byte first', function () {
    $spec = new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::MSB);
    $buffer = dirtyBuffer(2, 1, $spec);
    $buffer->setPixel(0, 0, 0xF800)->setPixel(1, 0, 0x07E0);

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->raw_data)->toBe([0xF8, 0x00, 0x07, 0xE0]);
});

it('splits multi-byte pixels low byte first when the spec says LSB', function () {
    $spec = new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B16, endianness: Endianness::LSB);
    $buffer = dirtyBuffer(2, 1, $spec);
    $buffer->setPixel(0, 0, 0xF800)->setPixel(1, 0, 0x07E0);

    expect($buffer->dump()[0]->raw_data)->toBe([0x00, 0xF8, 0xE0, 0x07]);
});

it('refuses to pack a non-ROW_MAJOR surface', function () {
    $spec = new FormatSpec(PixelFormat::MONO_HORIZONTAL, BitDepth::B1);
    $buffer = dirtyBuffer(8, 8, $spec);
    $buffer->setPixel(0, 0, 1);

    $buffer->dump();
})->throws(RuntimeException::class, 'ROW_MAJOR');

it('flush clears the retained canvas', function () {
    $buffer = dirtyBuffer(4, 4);
    $buffer->setPixel(0, 0, 9)->flush();

    // After flush the canvas is blank, so a forced repaint carries only zero bytes.
    $bytes = $buffer->markAllDirty()->dump()[0]->raw_data;

    expect(array_filter($bytes))->toBe([]);
});
