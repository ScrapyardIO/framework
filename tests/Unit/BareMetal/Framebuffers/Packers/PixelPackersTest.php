<?php

use BareMetal\Contracts\Framebuffers\Enums\PixelFormat;
use BareMetal\Contracts\Framebuffers\FramebufferException;
use BareMetal\Framebuffers\Packers\MonoHorizontalPacker;
use BareMetal\Framebuffers\Packers\PixelPackers;
use BareMetal\Framebuffers\Packers\RowMajorPacker;
use BareMetal\Framebuffers\Packers\VerticalPagePacker;
use DeptOfScrapyardRobotics\Tests\Fixtures\BareMetal\Framebuffers\FakePlanarPacker;

beforeEach(fn () => PixelPackers::reset());

afterEach(fn () => PixelPackers::reset());

test('every built-in pixel format resolves to its packer', function () {
    expect(PixelPackers::resolve(PixelFormat::ROW_MAJOR))->toBeInstanceOf(RowMajorPacker::class)
        ->and(PixelPackers::resolve(PixelFormat::MONO_VERTICAL_PAGE))->toBeInstanceOf(VerticalPagePacker::class)
        ->and(PixelPackers::resolve(PixelFormat::MONO_HORIZONTAL))->toBeInstanceOf(MonoHorizontalPacker::class);
});

test('resolving a format with no packer throws', function () {
    PixelPackers::resolve(PixelFormat::PLANAR);
})->throws(FramebufferException::class, "No packer registered for pixel format 'planar'.");

test('a registered packer covers a format with no built-in', function () {
    PixelPackers::register(PixelFormat::PLANAR, FakePlanarPacker::class);

    expect(PixelPackers::resolve(PixelFormat::PLANAR))->toBeInstanceOf(FakePlanarPacker::class);
});

test('a registration overrides the built-in default', function () {
    PixelPackers::register(PixelFormat::ROW_MAJOR, FakePlanarPacker::class);

    expect(PixelPackers::resolve(PixelFormat::ROW_MAJOR))->toBeInstanceOf(FakePlanarPacker::class);
});

test('reset() restores the built-in defaults', function () {
    PixelPackers::register(PixelFormat::ROW_MAJOR, FakePlanarPacker::class);
    PixelPackers::reset();

    expect(PixelPackers::resolve(PixelFormat::ROW_MAJOR))->toBeInstanceOf(RowMajorPacker::class);
});

test('registering a class that is not a PixelPacker throws', function () {
    PixelPackers::register(PixelFormat::PLANAR, stdClass::class);
})->throws(FramebufferException::class);
