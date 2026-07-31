<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\Endianness;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Contracts\Framebuffers\Framebuffer;
use Fabricate\Core\VisualPresentation;
use Fabricate\Framebuffers\DataObjects\DumpedBuffer;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Microscrap\GFX\PhpdaFruit\PhpdafruitGfx;

/**
 * Shared fixtures for the UX suites.
 *
 * Stage is exercised against a real VisualPresentation over a real PhpdafruitGfx
 * and a real framebuffer strategy, rather than mocks — the behaviour under test
 * is precisely how damage reaches a dirty-tracking buffer, which a mock would
 * define away.
 */
final class UxHarness
{
    public static function spec(BitDepth $depth = BitDepth::B16): FormatSpec
    {
        return new FormatSpec(PixelFormat::ROW_MAJOR, $depth, endianness: Endianness::MSB);
    }

    /**
     * A monochrome paged surface, the SSD1306 layout where damage granularity is
     * an 8-row page rather than a pixel.
     */
    public static function monoSpec(): FormatSpec
    {
        return new FormatSpec(PixelFormat::MONO_VERTICAL_PAGE, BitDepth::B1);
    }

    public static function presentation(
        Framebuffer $buffer,
        int $width,
        int $height,
        ?FormatSpec $spec = null,
        ?UxTestDisplay $display = null,
    ): VisualPresentation {
        return new VisualPresentation(
            $display ?? new UxTestDisplay($width, $height, $spec ?? self::spec()),
            $buffer,
            new PhpdafruitGfx($buffer),
        );
    }

    /**
     * Dirty regions as sorted inclusive bounds.
     *
     * Sorted so tests pin behaviour rather than the dirty set's internal merge
     * order, which is an implementation detail.
     *
     * @return array<int, array{0: int, 1: int, 2: int, 3: int}>
     */
    public static function dirtyBounds(Framebuffer $buffer): array
    {
        $bounds = array_map(
            fn (DumpedBuffer $update): array => (new Rect(
                $update->origin_x,
                $update->origin_y,
                $update->width,
                $update->height,
            ))->toBounds(),
            $buffer->dump(),
        );

        sort($bounds);

        return $bounds;
    }

    /**
     * The inclusive bounds of every non-zero pixel, or null when nothing was
     * painted at all.
     */
    public static function paintedBounds(Framebuffer $buffer, int $width, int $height): ?Rect
    {
        $left = PHP_INT_MAX;
        $top = PHP_INT_MAX;
        $right = -1;
        $bottom = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($buffer->getPixel($x, $y) === 0) {
                    continue;
                }

                $left = min($left, $x);
                $top = min($top, $y);
                $right = max($right, $x);
                $bottom = max($bottom, $y);
            }
        }

        return ($right < 0) ? null : Rect::fromBounds($left, $top, $right, $bottom);
    }
}
