<?php

use BareMetal\Contracts\Framebuffers\DTO\DumpedBuffer;
use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Enums\RenderType;
use BareMetal\Contracts\Framebuffers\FramebufferException;
use BareMetal\Framebuffers\FullFramebuffer;
use BareMetal\Framebuffers\FullFramebufferFactory;
use DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Framebuffers\FactorylessFramebuffer;

function fullMonoBuffer(int $width = 8, int $height = 8): FullFramebuffer
{
    return new FullFramebuffer($width, $height, new FormatSpec(
        PixelFormat::MONO_VERTICAL_PAGE,
        BitDepth::B1,
        bit_order: BitOrder::LSB_FIRST,
    ));
}

test('size() returns the FullFramebufferFactory carrying the dimensions', function () {
    $factory = FullFramebuffer::size(128, 64);

    expect($factory)->toBeInstanceOf(FullFramebufferFactory::class)
        ->and($factory->width)->toBe(128)
        ->and($factory->height)->toBe(64);
});

test('size() throws when a buffer never declared its factory class', function () {
    FactorylessFramebuffer::size(8, 8);
})->throws(FramebufferException::class);

test('dump() emits exactly one FULL update covering the whole surface', function () {
    $buffer = fullMonoBuffer()->setPixel(0, 0, 1);

    $dumps = $buffer->dump();

    expect($dumps)->toHaveCount(1)
        ->and($dumps[0])->toBeInstanceOf(DumpedBuffer::class)
        ->and($dumps[0]->render_type)->toBe(RenderType::FULL)
        ->and($dumps[0]->origin_x)->toBe(0)
        ->and($dumps[0]->origin_y)->toBe(0)
        ->and($dumps[0]->width)->toBe(8)
        ->and($dumps[0]->height)->toBe(8)
        ->and($dumps[0]->metadata)->toBe($buffer->formatSpec());
});

test('dump() payload is packed per the FormatSpec', function () {
    $buffer = fullMonoBuffer()->setPixel(0, 0, 1);

    $dumps = $buffer->dump();

    // 8x8 vertical pages, LSB first: (0, 0) is bit 0 of the first page byte.
    expect($dumps[0]->raw_data)->toBe([0x01, 0, 0, 0, 0, 0, 0, 0]);
});

test('flush() returns the dump and clears the surface', function () {
    $buffer = fullMonoBuffer()->setPixel(0, 0, 1);

    $flushed = $buffer->flush();

    expect($flushed[0]->raw_data)->toBe([0x01, 0, 0, 0, 0, 0, 0, 0])
        ->and($buffer->getPixel(0, 0))->toBe(0)
        ->and($buffer->dump()[0]->raw_data)->toBe([0, 0, 0, 0, 0, 0, 0, 0]);
});
