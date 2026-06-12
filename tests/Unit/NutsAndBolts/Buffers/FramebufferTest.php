<?php

use ScrapyardIO\NutsAndBolts\Buffers\FullFrameBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;

/*
 | Framebuffer is abstract, so its inherited drawing behaviour is exercised
 | through the only concrete subclass, FullFrameBuffer. State is read back via
 | the single full-frame dump it emits: a flat, row-major byte stream (B8 packs
 | one byte per pixel), so a cell at (x, y) lives at index y * width + x.
 */

function newBuffer(int $width, int $height): FullFrameBuffer
{
    return new FullFrameBuffer($width, $height, new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B8));
}

function gridOf(FullFrameBuffer $buffer): array
{
    return $buffer->dump()[0]->raw_data;
}

it('clips off-surface writes instead of throwing', function () {
    $buffer = newBuffer(2, 2);

    $buffer->setPixel(5, 5, 1);

    expect(gridOf($buffer))->toBe([0, 0, 0, 0]);
});

it('reflects a set pixel in the dumped data', function () {
    $buffer = newBuffer(3, 2);

    $buffer->setPixel(2, 1, 9);

    expect(gridOf($buffer)[(1 * 3) + 2])->toBe(9);
});

it('sets a region to a single shared value', function () {
    $buffer = newBuffer(2, 2);

    $buffer->setRegion([[0, 0], [1, 1]], 4);

    expect(gridOf($buffer))->toBe([4, 0, 0, 4]);
});

it('sets a group of cells to their own values', function () {
    $buffer = newBuffer(2, 2);

    $buffer->setPixels([[0, 0, 1], [1, 1, 2]]);

    expect(gridOf($buffer))->toBe([1, 0, 0, 2]);
});

it('fills a rectangular segment with a single value', function () {
    $buffer = newBuffer(3, 3);

    $buffer->setSegment(1, 0, 2, 2, 5);

    expect(gridOf($buffer))->toBe([0, 5, 5, 0, 5, 5, 0, 0, 0]);
});

it('clips an off-surface segment instead of throwing', function () {
    $buffer = newBuffer(2, 2);

    $buffer->setSegment(1, 1, 5, 5, 8);

    expect(gridOf($buffer))->toBe([0, 0, 0, 8]);
});

it('writes nothing for a non-positive segment size', function () {
    $buffer = newBuffer(2, 2);

    $buffer->setSegment(0, 0, 0, 3, 9);

    expect(gridOf($buffer))->toBe([0, 0, 0, 0]);
});

it('composites a source buffer with blitFrom at an offset', function () {
    $source = newBuffer(1, 1);
    $source->setPixel(0, 0, 7);

    $target = newBuffer(3, 3);
    $target->blitFrom($source, 1, 1);

    expect(gridOf($target)[(1 * 3) + 1])->toBe(7)
        ->and(gridOf($target)[0])->toBe(0);
});

it('mirrors blitFrom with blitTo', function () {
    $source = newBuffer(1, 1);
    $source->setPixel(0, 0, 7);

    $viaFrom = newBuffer(3, 3);
    $viaTo = newBuffer(3, 3);

    $viaFrom->blitFrom($source, 1, 1);
    $source->blitTo($viaTo, 1, 1);

    expect(gridOf($viaFrom))->toBe(gridOf($viaTo));
});

it('returns the buffer from drawing primitives for chaining', function () {
    $buffer = newBuffer(2, 2);

    expect($buffer->setPixel(0, 0, 1))->toBe($buffer)
        ->and($buffer->setRegion([[1, 1]], 2))->toBe($buffer)
        ->and($buffer->setPixels([[0, 1, 3]]))->toBe($buffer)
        ->and($buffer->setSegment(0, 0, 1, 1, 4))->toBe($buffer);
});
