<?php

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Enums\RenderType;
use BareMetal\Framebuffers\PageSegmentBuffer;
use BareMetal\Framebuffers\PageSegmentBufferFactory;

function pageBuffer(int $width = 8, int $height = 16): PageSegmentBuffer
{
    return new PageSegmentBuffer(
        $width,
        $height,
        new FormatSpec(PixelFormat::MONO_VERTICAL_PAGE, BitDepth::B1, bit_order: BitOrder::LSB_FIRST),
    );
}

test('size() returns the PageSegmentBufferFactory carrying the dimensions', function () {
    $factory = PageSegmentBuffer::size(128, 64);

    expect($factory)->toBeInstanceOf(PageSegmentBufferFactory::class)
        ->and($factory->width)->toBe(128)
        ->and($factory->height)->toBe(64);
});

it('emits nothing when no writes happened', function () {
    expect(pageBuffer()->dump())->toBe([]);
});

it('ships only the touched page as a partial update', function () {
    $buffer = pageBuffer(8, 16);
    $buffer->setPixel(0, 0, 1);

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->render_type)->toBe(RenderType::PARTIAL)
        ->and($updates[0]->origin_y)->toBe(0)
        ->and($updates[0]->width)->toBe(8)
        ->and($updates[0]->height)->toBe(8)
        ->and($updates[0]->raw_data[0])->toBe(1);
});

it('addresses a lower page by its row origin', function () {
    $buffer = pageBuffer(8, 16);
    $buffer->setPixel(0, 8, 1);

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(1)
        ->and($updates[0]->origin_y)->toBe(8)
        ->and($updates[0]->raw_data[0])->toBe(1);
});

it('emits one update per dirty page in order', function () {
    $buffer = pageBuffer(8, 16);
    $buffer->setPixel(0, 8, 1)->setPixel(0, 0, 1);

    $origins = array_map(fn ($update) => $update->origin_y, $buffer->dump());

    expect($origins)->toBe([0, 8]);
});

it('packs each page as golden vertical-page bytes', function () {
    // 4x16 canvas, LSB first: page 0 gets a full column at x=1 (0xFF) and a
    // single top-row bit at x=3 (0x01); page 1 gets rows 8+9 at x=0 (0b11).
    $buffer = pageBuffer(4, 16);

    for ($y = 0; $y < 8; $y++) {
        $buffer->setPixel(1, $y, 1);
    }
    $buffer->setPixel(3, 0, 1);
    $buffer->setPixel(0, 8, 1)->setPixel(0, 9, 1);

    $updates = $buffer->dump();

    expect($updates)->toHaveCount(2)
        ->and($updates[0]->origin_y)->toBe(0)
        ->and($updates[0]->raw_data)->toBe([0x00, 0xFF, 0x00, 0x01])
        ->and($updates[1]->origin_y)->toBe(8)
        ->and($updates[1]->raw_data)->toBe([0x03, 0x00, 0x00, 0x00]);
});

it('marks pages dirty through setSegment() spans', function () {
    $buffer = pageBuffer(8, 16);
    $buffer->setSegment(0, 6, 2, 4, 1); // straddles the page 0 / page 1 seam

    $origins = array_map(fn ($update) => $update->origin_y, $buffer->dump());

    expect($origins)->toBe([0, 8]);
});

it('clears the dirty set after dumping', function () {
    $buffer = pageBuffer(8, 16);
    $buffer->setPixel(0, 0, 1);
    $buffer->dump();

    expect($buffer->dump())->toBe([]);
});

it('retains the canvas across dumps so a repaint re-emits the same bytes', function () {
    $buffer = pageBuffer(8, 16);
    $buffer->setPixel(0, 0, 1);

    $first = $buffer->dump();
    $again = $buffer->markAllDirty()->dump();

    expect($again[0]->raw_data)->toBe($first[0]->raw_data);
});

it('repaints every page when marked all dirty', function () {
    $buffer = pageBuffer(8, 16);

    expect($buffer->markAllDirty()->dump())->toHaveCount(2);
});

it('clamps the final partial page to the remaining rows', function () {
    $buffer = pageBuffer(8, 12);
    $buffer->markAllDirty();

    $updates = $buffer->dump();

    expect($updates[1]->origin_y)->toBe(8)
        ->and($updates[1]->height)->toBe(4);
});

it('flush clears the retained canvas', function () {
    $buffer = pageBuffer(8, 16);
    $buffer->setPixel(0, 0, 1)->flush();

    // After flush the canvas is blank, so a forced repaint carries only zero bytes.
    $bytes = $buffer->markAllDirty()->dump()[0]->raw_data;

    expect(array_filter($bytes))->toBe([]);
});
