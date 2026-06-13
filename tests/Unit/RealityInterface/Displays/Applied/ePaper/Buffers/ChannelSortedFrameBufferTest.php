<?php

use RealityInterface\Displays\Applied\ePaper\Buffers\ChannelSortedFrameBuffer;
use RealityInterface\Displays\Applied\ePaper\Enums\EInkColor;
use ScrapyardIO\NutsAndBolts\DataObjects\ChannelPalette;
use ScrapyardIO\NutsAndBolts\DataObjects\ChannelSpec;
use ScrapyardIO\NutsAndBolts\DataObjects\DumpedBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\BitOrder;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\RenderType;

/*
 | A black plane is inverted (SSD1680 sense: black packs as 0, background as 1),
 | so a lone black pixel at x0 of an 8-wide row reads MSB..LSB = 0,1,1,1,1,1,1,1
 | = 0x7F. A red plane is straight (red = 1), so a red pixel at x1 reads
 | 0,1,0,0,0,0,0,0 = 0x40.
 */

function bwSpec(): FormatSpec
{
    return new FormatSpec(
        PixelFormat::MONO_HORIZONTAL,
        BitDepth::B1,
        bit_order: BitOrder::MSB_FIRST,
        palette: new ChannelPalette(
            new ChannelSpec(EInkColor::BLACK->value, inverted: true),
        ),
    );
}

function bwrSpec(): FormatSpec
{
    return new FormatSpec(
        PixelFormat::MONO_HORIZONTAL,
        BitDepth::B1,
        bit_order: BitOrder::MSB_FIRST,
        palette: new ChannelPalette(
            new ChannelSpec(EInkColor::BLACK->value, inverted: true),
            new ChannelSpec(EInkColor::RED->value),
        ),
    );
}

it('requires a format spec carrying a channel palette', function () {
    new ChannelSortedFrameBuffer(8, 1, new FormatSpec(PixelFormat::MONO_HORIZONTAL, BitDepth::B1));
})->throws(InvalidArgumentException::class);

it('packs a black pixel into the inverted black channel', function () {
    $buffer = new ChannelSortedFrameBuffer(8, 1, bwSpec());

    $buffer->setPixel(0, 0, EInkColor::BLACK->value);

    expect($buffer->dump()[0]->raw_data)->toBe([EInkColor::BLACK->value => [0x7F]]);
});

it('omits a channel that never received its colour', function () {
    $buffer = new ChannelSortedFrameBuffer(8, 1, bwSpec());

    $buffer->setPixel(0, 0, EInkColor::WHITE->value);

    expect($buffer->dump()[0]->raw_data)->toBe([]);
});

it('sorts black and red into separate channels', function () {
    $buffer = new ChannelSortedFrameBuffer(8, 1, bwrSpec());

    $buffer->setPixel(0, 0, EInkColor::BLACK->value)
        ->setPixel(1, 0, EInkColor::RED->value);

    expect($buffer->dump()[0]->raw_data)->toBe([
        EInkColor::BLACK->value => [0x7F],
        EInkColor::RED->value => [0x40],
    ]);
});

it('clears the other planes when a pixel is recoloured', function () {
    $buffer = new ChannelSortedFrameBuffer(8, 1, bwrSpec());

    $buffer->setPixel(0, 0, EInkColor::BLACK->value)
        ->setPixel(0, 0, EInkColor::RED->value);

    expect($buffer->dump()[0]->raw_data)->toBe([EInkColor::RED->value => [0x80]]);
});

it('fills a rectangular segment in the target channel', function () {
    $buffer = new ChannelSortedFrameBuffer(8, 2, bwrSpec());

    $buffer->setSegment(0, 0, 8, 1, EInkColor::RED->value);

    expect($buffer->dump()[0]->raw_data)->toBe([EInkColor::RED->value => [0xFF, 0x00]]);
});

it('clips off-surface writes instead of throwing', function () {
    $buffer = new ChannelSortedFrameBuffer(8, 1, bwSpec());

    $buffer->setPixel(99, 99, EInkColor::BLACK->value);

    expect($buffer->dump()[0]->raw_data)->toBe([]);
});

it('pads each row to a byte boundary with the background sense', function () {
    $buffer = new ChannelSortedFrameBuffer(10, 1, bwSpec());

    $buffer->setPixel(0, 0, EInkColor::BLACK->value);

    expect($buffer->dump()[0]->raw_data)->toBe([EInkColor::BLACK->value => [0x7F, 0xFF]]);
});

it('clears the planes after a flush', function () {
    $buffer = new ChannelSortedFrameBuffer(8, 1, bwSpec());
    $buffer->setPixel(0, 0, EInkColor::BLACK->value);

    $flushed = $buffer->flush()[0]->raw_data;

    expect($flushed)->toBe([EInkColor::BLACK->value => [0x7F]])
        ->and($buffer->dump()[0]->raw_data)->toBe([]);
});

it('emits a single full update carrying its format spec', function () {
    $spec = bwSpec();
    $buffer = new ChannelSortedFrameBuffer(8, 1, $spec);
    $buffer->setPixel(0, 0, EInkColor::BLACK->value);

    $dump = $buffer->dump();

    expect($dump)->toHaveCount(1)
        ->and($dump[0])->toBeInstanceOf(DumpedBuffer::class)
        ->and($dump[0]->render_type)->toBe(RenderType::FULL)
        ->and($dump[0]->metadata)->toBe($spec)
        ->and($dump[0]->width)->toBe(8)
        ->and($dump[0]->height)->toBe(1);
});
