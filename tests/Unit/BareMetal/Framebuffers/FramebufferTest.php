<?php

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Framebuffers\FullFramebuffer;
use DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Framebuffers\ForeignFramebuffer;

function makeBuffer(int $width = 8, int $height = 8): FullFramebuffer
{
    return new FullFramebuffer($width, $height, new FormatSpec(
        PixelFormat::MONO_HORIZONTAL,
        BitDepth::B1,
        bit_order: BitOrder::MSB_FIRST,
    ));
}

test('the viewport reports the constructed dimensions', function () {
    $buffer = makeBuffer(12, 5);

    expect($buffer->viewportWidth())->toBe(12)
        ->and($buffer->viewportHeight())->toBe(5);
});

test('setPixel() and getPixel() round-trip and are fluent', function () {
    $buffer = makeBuffer();

    $result = $buffer->setPixel(3, 4, 7);

    expect($result)->toBe($buffer)
        ->and($buffer->getPixel(3, 4))->toBe(7);
});

test('off-surface writes are silently dropped and off-surface reads return 0', function () {
    $buffer = makeBuffer(4, 4);

    $buffer->setPixel(-1, 0, 9)->setPixel(0, -1, 9)->setPixel(4, 0, 9)->setPixel(0, 4, 9);

    expect($buffer->getPixel(-1, 0))->toBe(0)
        ->and($buffer->getPixel(4, 0))->toBe(0)
        ->and($buffer->getPixel(0, 0))->toBe(0);
});

test('setRegion() sets every coordinate to the shared value', function () {
    $buffer = makeBuffer();

    $buffer->setRegion([[0, 0], [1, 1], [2, 2]], 5);

    expect($buffer->getPixel(0, 0))->toBe(5)
        ->and($buffer->getPixel(1, 1))->toBe(5)
        ->and($buffer->getPixel(2, 2))->toBe(5)
        ->and($buffer->getPixel(1, 0))->toBe(0);
});

test('setPixels() sets each cell to its own value', function () {
    $buffer = makeBuffer();

    $buffer->setPixels([[0, 0, 1], [1, 0, 2], [2, 0, 3]]);

    expect($buffer->getPixel(0, 0))->toBe(1)
        ->and($buffer->getPixel(1, 0))->toBe(2)
        ->and($buffer->getPixel(2, 0))->toBe(3);
});

test('setSegment() fills a rectangle and clips at the surface edge', function () {
    $buffer = makeBuffer(4, 4);

    $buffer->setSegment(2, 2, 4, 4, 1);

    expect($buffer->getPixel(2, 2))->toBe(1)
        ->and($buffer->getPixel(3, 3))->toBe(1)
        ->and($buffer->getPixel(1, 1))->toBe(0);
});

test('setSegment() with non-positive dimensions writes nothing', function () {
    $buffer = makeBuffer();

    $buffer->setSegment(0, 0, 0, 5, 1)->setSegment(0, 0, 5, -1, 1);

    expect($buffer->getPixel(0, 0))->toBe(0);
});

test('blitFrom() composites a sibling buffer at the given offset with clipping', function () {
    $source = makeBuffer(2, 2)->setPixels([[0, 0, 1], [1, 1, 2]]);
    $target = makeBuffer(4, 4);

    $result = $target->blitFrom($source, 3, 3);

    expect($result)->toBe($target)
        ->and($target->getPixel(3, 3))->toBe(1)
        // (4, 4) falls off the target surface and is clipped.
        ->and($target->getPixel(3, 4))->toBe(0);
});

test('blitTo() delegates to the target and returns it', function () {
    $source = makeBuffer(2, 2)->setPixel(1, 0, 6);
    $target = makeBuffer(4, 4);

    $result = $source->blitTo($target, 1, 1);

    expect($result)->toBe($target)
        ->and($target->getPixel(2, 1))->toBe(6);
});

test('blitFrom() accepts any implementation of the Framebuffer contract', function () {
    $foreign = (new ForeignFramebuffer(2, 2))->setPixel(0, 0, 9);
    $target = makeBuffer(4, 4);

    $target->blitFrom($foreign, 1, 1);

    expect($target->getPixel(1, 1))->toBe(9);
});

test('blitTo() works against any implementation of the Framebuffer contract', function () {
    $source = makeBuffer(2, 2)->setPixel(0, 0, 4);
    $foreign = new ForeignFramebuffer(4, 4);

    $source->blitTo($foreign, 2, 2);

    expect($foreign->getPixel(2, 2))->toBe(4);
});
