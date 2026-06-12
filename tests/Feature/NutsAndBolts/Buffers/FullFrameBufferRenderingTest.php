<?php

use ScrapyardIO\NutsAndBolts\Buffers\FullFrameBuffer;
use ScrapyardIO\NutsAndBolts\DataObjects\FormatSpec;
use ScrapyardIO\NutsAndBolts\Enums\BitDepth;
use ScrapyardIO\NutsAndBolts\Enums\BitOrder;
use ScrapyardIO\NutsAndBolts\Enums\PageAxis;
use ScrapyardIO\NutsAndBolts\Enums\PixelFormat;
use ScrapyardIO\NutsAndBolts\Enums\RenderType;
use ScrapyardIO\NutsAndBolts\Enums\ScanDirection;

/*
 | Stand-in for the upcoming pipeline: draw on a buffer, flush, and consume the
 | emitted update the way a downstream transcoder/display will. When the buffer
 | already speaks the display's format, the transcode step is a no-op.
 */

it('hands a downstream consumer a full frame it can use directly', function () {
    $monochromeVerticalPage = fn (): FormatSpec => new FormatSpec(
        pixel_format: PixelFormat::MONO_VERTICAL_PAGE,
        bit_depth: BitDepth::B1,
        scan_direction: ScanDirection::TOP_TO_BOTTOM,
        bit_order: BitOrder::MSB_FIRST,
        page_axis: PageAxis::VERTICAL,
    );

    $buffer = FullFrameBuffer::size(2, 2)
        ->pixelFormat(PixelFormat::MONO_VERTICAL_PAGE)
        ->bitDepth(BitDepth::B1)
        ->bitOrder(BitOrder::MSB_FIRST)
        ->pageAxis(PageAxis::VERTICAL)
        ->build();

    $buffer->setPixel(0, 0, 1)->setPixel(1, 1, 1);

    $updates = $buffer->flush();

    expect($updates)->toHaveCount(1);

    $frame = $updates[0];

    // The display advertises the same spec, so a transcoder would pass it through untouched.
    $displayExpects = $monochromeVerticalPage();

    // raw_data is already packed to the spec: vertical pages, MSB-first means the
    // top row lands on bit 7. (0,0) -> col 0 bit 7 = 128; (1,1) -> col 1 bit 6 = 64.
    expect($frame->render_type)->toBe(RenderType::FULL)
        ->and($frame->metadata)->toEqual($displayExpects)
        ->and($frame->origin_x)->toBe(0)
        ->and($frame->origin_y)->toBe(0)
        ->and($frame->raw_data)->toBe([128, 64]);

    // Flushing reset the surface for the next frame.
    expect($buffer->dump()[0]->raw_data)->toBe([0, 0]);
});
