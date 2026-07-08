<?php

use BareMetal\Contracts\Framebuffers\DTO\FormatSpec;
use BareMetal\Contracts\Framebuffers\Enums\BitDepth;
use BareMetal\Contracts\Framebuffers\Enums\BitOrder;
use BareMetal\Contracts\Framebuffers\Enums\Endianness;
use BareMetal\Contracts\Framebuffers\Enums\PageAxis;
use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\Enums\RenderType;
use BareMetal\Contracts\Framebuffers\Enums\ScanDirection;
use BareMetal\Contracts\GFX\RendererException;
use BareMetal\Displays\DisplayComponent;
use BareMetal\Displays\ElectronicInk\ePaperDisplay;
use BareMetal\Displays\FullColor\FullColorDisplay;
use BareMetal\Displays\Monochrome\MonochromeDisplay;
use BareMetal\Displays\Windowed\WindowedDisplay;
use BareMetal\Framebuffers\DirtyRegionsBuffer;
use BareMetal\Framebuffers\FullFramebuffer;
use BareMetal\Framebuffers\PageSegmentBuffer;
use BareMetal\GFX\RenderingLibraries;
use DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Displays\FakeEPaperDisplay;
use DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Displays\FakeFullColorDisplay;
use DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Displays\FakeMonochromeDisplay;
use DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Displays\FakeWindowedDisplay;
use DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\GFX\FakeRenderer;

/**
 * RenderingLibraries is a singleton, so each test resets it and boots it with
 * the FakeRenderer instead of the real defaults — those are discovered from
 * sibling renderer packages (microscrap/phpdafruit-gfx, microscrap/sdl3-gfx)
 * this framework package doesn't itself depend on.
 */
function resetRenderingLibraries(): void
{
    $property = (new ReflectionClass(RenderingLibraries::class))->getProperty('instance');
    $property->setAccessible(true);
    $property->setValue(null, null);
}

function monoPageSpec(): FormatSpec
{
    return new FormatSpec(
        PixelFormat::MONO_VERTICAL_PAGE,
        BitDepth::B1,
        ScanDirection::TOP_TO_BOTTOM,
        BitOrder::LSB_FIRST,
        page_axis: PageAxis::VERTICAL,
    );
}

function rgbaSpec(): FormatSpec
{
    return new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B32, endianness: Endianness::MSB);
}

beforeEach(function () {
    resetRenderingLibraries();
    RenderingLibraries::boot(['phpdafruit' => FakeRenderer::class, 'sdl3' => FakeRenderer::class]);
});

afterEach(fn () => resetRenderingLibraries());

// -- Construction resolution --------------------------------------------------

test('defaults resolve the phpdafruit library over the subclass default framebuffer', function () {
    $display = new FakeMonochromeDisplay(16, 16, monoPageSpec());

    $component = new MonochromeDisplay($display);

    expect($component->renderer())->toBeInstanceOf(FakeRenderer::class)
        ->and($component->framebuffer())->toBeInstanceOf(PageSegmentBuffer::class)
        ->and($component->renderer()->buffer())->toBe($component->framebuffer());
});

test('a FullColorDisplay defaults to a DirtyRegionsBuffer', function () {
    $display = new FakeFullColorDisplay(4, 4, rgbaSpec());

    expect((new FullColorDisplay($display))->framebuffer())->toBeInstanceOf(DirtyRegionsBuffer::class);
});

test('an ePaperDisplay defaults to a FullFramebuffer', function () {
    $display = new FakeEPaperDisplay(8, 8, new FormatSpec(
        PixelFormat::MONO_HORIZONTAL,
        BitDepth::B1,
        bit_order: BitOrder::MSB_FIRST,
    ));

    expect((new ePaperDisplay($display))->framebuffer())->toBeInstanceOf(FullFramebuffer::class);
});

test('the base component asks the default renderer for its preferred framebuffer', function () {
    $display = new FakeMonochromeDisplay(16, 16, monoPageSpec());

    // FakeRenderer prefers a PageSegmentBuffer for mono page specs.
    expect((new DisplayComponent($display))->framebuffer())->toBeInstanceOf(PageSegmentBuffer::class);
});

test('a windowed component defaults to the sdl3 library', function () {
    resetRenderingLibraries();
    RenderingLibraries::boot(['sdl3' => FakeRenderer::class]);

    $display = new FakeWindowedDisplay(4, 4, rgbaSpec());

    expect((new WindowedDisplay($display))->renderer())->toBeInstanceOf(FakeRenderer::class);
});

test('an uninstalled default renderer raises a typed exception listing what is installed', function () {
    resetRenderingLibraries();
    RenderingLibraries::boot(['sdl3' => FakeRenderer::class]);

    $display = new FakeMonochromeDisplay(16, 16, monoPageSpec());

    expect(fn () => new MonochromeDisplay($display))
        ->toThrow(RendererException::class, 'phpdafruit');
});

test('an injected renderer is used as-is with its own buffer', function () {
    $display = new FakeMonochromeDisplay(16, 16, monoPageSpec());
    $renderer = new FakeRenderer(new PageSegmentBuffer(16, 16, monoPageSpec()));

    $component = new MonochromeDisplay($display, renderer: $renderer);

    expect($component->renderer())->toBe($renderer)
        ->and($component->framebuffer())->toBe($renderer->buffer());
});

test('a framebuffer injected alongside a renderer is ignored in favor of the renderer buffer', function () {
    $display = new FakeMonochromeDisplay(16, 16, monoPageSpec());
    $renderer = new FakeRenderer(new PageSegmentBuffer(16, 16, monoPageSpec()));
    $bystander = new PageSegmentBuffer(16, 16, monoPageSpec());

    $component = new MonochromeDisplay($display, $bystander, $renderer);

    // The renderer only ever draws into its own buffer, so that is the
    // component's authoritative surface.
    expect($component->framebuffer())->toBe($renderer->buffer())
        ->and($component->framebuffer())->not->toBe($bystander);
});

// -- Drawing API proxy ---------------------------------------------------------

test('drawing methods proxy to the renderer and stay fluent on the component', function () {
    $display = new FakeMonochromeDisplay(16, 16, monoPageSpec());
    $component = new MonochromeDisplay($display);

    /** @var FakeRenderer $renderer */
    $renderer = $component->renderer();

    $result = $component
        ->drawPixel(0, 0, 1)
        ->drawLine(0, 0, 3, 3, 1)
        ->fillRect(1, 1, 2, 2, 1)
        ->drawCircle(4, 4, 2, 1)
        ->setCursor(0, 8)
        ->print('hi');

    expect($result)->toBe($component)
        ->and($renderer->calls)->toBe([
            'drawPixel', 'drawLine', 'fillRect', 'drawCircle', 'setCursor', 'print',
        ]);
});

test('getTextBounds returns the renderer measurement', function () {
    $display = new FakeMonochromeDisplay(16, 16, monoPageSpec());
    $component = new MonochromeDisplay($display);

    expect($component->getTextBounds('hi', 2, 3))->toBe(['x1' => 2, 'y1' => 3, 'w' => 0, 'h' => 0]);
});

// -- render()/flush() orchestration --------------------------------------------

test('the default setup transmits untranscoded bytes via the fast path', function () {
    $display = new FakeMonochromeDisplay(16, 16, monoPageSpec());
    $component = new MonochromeDisplay($display);

    $component->drawPixel(0, 0, 1)->render();

    // A full refresh of a 16x16 page buffer is two 8-row pages of 16 bytes.
    expect($display->transmitted)->toHaveCount(2)
        ->and($display->transmitted[0]->metadata)->toBe($display->formatSpec())
        ->and($display->transmitted[0]->raw_data)->toBe([0x01, ...array_fill(0, 15, 0x00)])
        ->and($display->transmitted[1]->raw_data)->toBe(array_fill(0, 16, 0x00));
});

test('a partial refresh only transmits the dirty pages', function () {
    $display = new FakeMonochromeDisplay(16, 16, monoPageSpec());
    $component = new MonochromeDisplay($display);

    $component->drawPixel(0, 0, 1)->render(partial_refresh: true);

    expect($display->transmitted)->toHaveCount(1)
        ->and($display->transmitted[0]->render_type)->toBe(RenderType::PARTIAL)
        ->and($display->transmitted[0]->origin_y)->toBe(0);

    // Nothing new drawn: a second partial refresh has nothing to send.
    $component->render(partial_refresh: true);

    expect($display->transmitted)->toHaveCount(1);
});

test('a mismatched injected renderer produces transcoded frames', function () {
    $display = new FakeFullColorDisplay(2, 8, rgbaSpec());

    $mono_spec = new FormatSpec(
        PixelFormat::MONO_VERTICAL_PAGE,
        BitDepth::B1,
        bit_order: BitOrder::LSB_FIRST,
        page_axis: PageAxis::VERTICAL,
    );
    $renderer = new FakeRenderer(new PageSegmentBuffer(2, 8, $mono_spec));

    (new FullColorDisplay($display, renderer: $renderer))
        ->drawPixel(0, 0, 1)
        ->render();

    expect($display->transmitted)->toHaveCount(1);

    $frame = $display->transmitted[0];
    $pixels = array_chunk($frame->raw_data, 4);

    expect($frame->metadata)->toEqual($display->formatSpec())
        ->and($frame->metadata->pixel_format)->toBe(PixelFormat::ROW_MAJOR)
        ->and($pixels)->toHaveCount(16)
        ->and($pixels[0])->toBe([0xFF, 0xFF, 0xFF, 0xFF])
        ->and($pixels[1])->toBe([0x00, 0x00, 0x00, 0xFF]);
});

test('flush transmits like a full render and then clears the drawn state', function () {
    $display = new FakeMonochromeDisplay(16, 16, monoPageSpec());
    $component = new MonochromeDisplay($display);

    $component->drawPixel(0, 0, 1)->flush();

    expect($display->transmitted)->toHaveCount(2)
        ->and($display->transmitted[0]->raw_data[0])->toBe(0x01)
        ->and($component->framebuffer()->getPixel(0, 0))->toBe(0);

    // The drawn state is gone: a partial refresh finds nothing dirty.
    $component->render(partial_refresh: true);

    expect($display->transmitted)->toHaveCount(2);
});

test('render keeps the drawn state intact for the next frame', function () {
    $display = new FakeMonochromeDisplay(16, 16, monoPageSpec());
    $component = new MonochromeDisplay($display);

    $component->drawPixel(0, 0, 1)->render();

    expect($component->framebuffer()->getPixel(0, 0))->toBe(1);

    // A second full render re-emits the same pixel.
    $component->render();

    expect($display->transmitted)->toHaveCount(4)
        ->and($display->transmitted[2]->raw_data[0])->toBe(0x01);
});
