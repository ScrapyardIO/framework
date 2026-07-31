<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Framebuffers\FormatSpec;
use Fabricate\UX\Color;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A node expresses a colour once, so the packing for every supported depth is
 * pinned as golden bytes here — a regression would silently change what every
 * node paints rather than failing loudly.
 */
class ColorTest extends TestCase
{
    /**
     * White, black and saturated red on every depth the enum defines.
     *
     * These are the arms of Sdl3Framebuffer::unmapColor(), which the plan names
     * as the single packing convention for this stack.
     *
     * @return array<string, array{0: BitDepth, 1: int, 2: int, 3: int}>
     */
    public static function packings(): array
    {
        return [
            //                       depth,          white,       black, red
            'mono' => [BitDepth::B1, 0x1, 0x0, 0x1],
            '8-bit red channel' => [BitDepth::B8, 0xFF, 0x0, 0xFF],
            '10-bit via the RGB888 arm' => [BitDepth::B10, 0xFFFFFF, 0x0, 0xFF0000],
            'RGB444' => [BitDepth::B12, 0xFFF, 0x0, 0xF00],
            'RGB565' => [BitDepth::B16, 0xFFFF, 0x0, 0xF800],
            'RGB666' => [BitDepth::B18, 0xFCFCFC, 0x0, 0xFC0000],
            'RGB888' => [BitDepth::B24, 0xFFFFFF, 0x0, 0xFF0000],
            'RGBA8888' => [BitDepth::B32, 0xFFFFFFFF, 0xFF, 0xFF0000FF],
        ];
    }

    #[DataProvider('packings')]
    public function testItPacksEachDepthToTheExpectedBytes(
        BitDepth $depth,
        int $white,
        int $black,
        int $red,
    ): void {
        $spec = new FormatSpec(PixelFormat::ROW_MAJOR, $depth);

        $this->assertSame($white, Color::white()->resolveFor($spec), 'white');
        $this->assertSame($black, Color::black()->resolveFor($spec), 'black');
        $this->assertSame($red, Color::rgb(255, 0, 0)->resolveFor($spec), 'red');
    }

    /**
     * The depths VisualPresentation::defaultTextColor() names explicitly, paired
     * with the ink it picks for each.
     *
     * @return array<string, array{0: BitDepth, 1: int}>
     */
    public static function frameworkDefaultInk(): array
    {
        return [
            'mono' => [BitDepth::B1, 1],
            'RGB444' => [BitDepth::B12, 0x0FFF],
            'RGB565' => [BitDepth::B16, 0xFFFF],
            'RGB666' => [BitDepth::B18, 0xFCFCFC],
            'RGBA8888' => [BitDepth::B32, 0xFFFFFFFF],
        ];
    }

    /**
     * The whole point of the packings above is that they agree with the ink the
     * framework already picks per depth, so a white Color and the pre-existing
     * default white are the same bytes rather than merely similar.
     */
    #[DataProvider('frameworkDefaultInk')]
    public function testWhiteMatchesTheFrameworkDefaultInk(BitDepth $depth, int $expected_ink): void
    {
        $white = Color::white()->resolveFor(new FormatSpec(PixelFormat::ROW_MAJOR, $depth));

        $this->assertSame($expected_ink, $white);
    }

    /**
     * @return array<string, array{0: BitDepth}>
     */
    public static function everyDepth(): array
    {
        return array_map(fn (BitDepth $depth): array => [$depth], array_column(BitDepth::cases(), null, 'name'));
    }

    /**
     * Black on RGBA is 0x000000FF, not zero, because it is opaque ink. Only a
     * fully transparent colour collapses to zero.
     */
    public function testOpaqueBlackIsNotZeroOnRgba(): void
    {
        $spec = new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B32);

        $this->assertSame(0xFF, Color::black()->resolveFor($spec));
        $this->assertSame(0x0, Color::transparent()->resolveFor($spec));
    }

    /**
     * Transparent means "no ink" on every depth, which is also the unlit value on
     * a monochrome panel.
     */
    #[DataProvider('everyDepth')]
    public function testTransparentResolvesToZeroEverywhere(BitDepth $depth): void
    {
        $this->assertSame(0, Color::transparent()->resolveFor(new FormatSpec(PixelFormat::ROW_MAJOR, $depth)));
    }

    /**
     * Mono lights on *any* channel above half scale, not on luminance. A luma
     * rule would leave saturated red dark on an SSD1306, where this stack's
     * existing convention lights it — the two are not interchangeable.
     */
    public function testMonochromeLightsOnAnyChannelAboveHalfScale(): void
    {
        $mono = new FormatSpec(PixelFormat::ROW_MAJOR, BitDepth::B1);

        $this->assertSame(1, Color::rgb(255, 0, 0)->resolveFor($mono), 'saturated red must light');
        $this->assertSame(1, Color::rgb(0, 255, 0)->resolveFor($mono));
        $this->assertSame(1, Color::rgb(0, 0, 255)->resolveFor($mono), 'saturated blue must light too');
        $this->assertSame(1, Color::grey(128)->resolveFor($mono), 'just above half scale lights');
        $this->assertSame(0, Color::grey(127)->resolveFor($mono), 'half scale itself does not');
        $this->assertSame(0, Color::black()->resolveFor($mono));
    }

    /**
     * A paged SSD1306 layout is monochrome by pixel format, so it must resolve as
     * mono regardless of what the depth says.
     */
    public function testMonoPixelFormatsResolveAsMonochrome(): void
    {
        foreach ([PixelFormat::MONO_VERTICAL_PAGE, PixelFormat::MONO_HORIZONTAL] as $format) {
            $spec = new FormatSpec($format, BitDepth::B16);

            $this->assertSame(1, Color::white()->resolveFor($spec), $format->name);
            $this->assertSame(0, Color::black()->resolveFor($spec), $format->name);
        }
    }

    /**
     * The guard against this drifting back apart: the packing is re-derived here
     * from Sdl3Framebuffer::unmapColor()'s arms, expressed in RGBA terms. Copied
     * deliberately rather than imported, because SDL is not a dependency of the
     * framework suite — if either side changes, this fails.
     *
     * @return array<string, array{0: Color}>
     */
    public static function conformanceColors(): array
    {
        return [
            'white' => [Color::white()],
            'black' => [Color::black()],
            'red' => [Color::rgb(255, 0, 0)],
            'green' => [Color::rgb(0, 255, 0)],
            'blue' => [Color::rgb(0, 0, 255)],
            'mid grey' => [Color::grey(128)],
            'arbitrary' => [Color::rgb(23, 199, 77)],
        ];
    }

    #[DataProvider('conformanceColors')]
    public function testPackingMatchesTheSurfaceUnmapConvention(Color $color): void
    {
        [$r, $g, $b, $a] = [$color->red, $color->green, $color->blue, $color->alpha];

        $expected = [
            BitDepth::B1->name => (($r > 127) || ($g > 127) || ($b > 127)) ? 1 : 0,
            BitDepth::B8->name => $r,
            BitDepth::B10->name => ($r << 16) | ($g << 8) | $b,
            BitDepth::B12->name => (($r >> 4) << 8) | (($g >> 4) << 4) | ($b >> 4),
            BitDepth::B16->name => (($r >> 3) << 11) | (($g >> 2) << 5) | ($b >> 3),
            BitDepth::B18->name => (($r & 0xFC) << 16) | (($g & 0xFC) << 8) | ($b & 0xFC),
            BitDepth::B24->name => ($r << 16) | ($g << 8) | $b,
            BitDepth::B32->name => ($r << 24) | ($g << 16) | ($b << 8) | $a,
        ];

        foreach (BitDepth::cases() as $depth) {
            $this->assertSame(
                $expected[$depth->name],
                $color->resolveFor(new FormatSpec(PixelFormat::ROW_MAJOR, $depth)),
                "{$depth->name} disagrees with the surface unmap convention.",
            );
        }
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: int, 3: int, 4: int}>
     */
    public static function hexStrings(): array
    {
        return [
            'long with hash' => ['#FF8800', 255, 136, 0, 255],
            'long without hash' => ['FF8800', 255, 136, 0, 255],
            'lowercase' => ['#ff8800', 255, 136, 0, 255],
            'shorthand' => ['#F80', 255, 136, 0, 255],
            'with alpha' => ['#FF880080', 255, 136, 0, 128],
            'shorthand with alpha' => ['#F808', 255, 136, 0, 136],
        ];
    }

    #[DataProvider('hexStrings')]
    public function testItParsesHex(string $hex, int $red, int $green, int $blue, int $alpha): void
    {
        $color = Color::fromHex($hex);

        $this->assertSame([$red, $green, $blue, $alpha], [$color->red, $color->green, $color->blue, $color->alpha]);
    }

    /**
     * Shorthand doubles each digit rather than padding with zeroes, so #F80 is
     * #FF8800 and not #F08000.
     */
    public function testShorthandExpandsByDoublingDigits(): void
    {
        $this->assertTrue(Color::fromHex('#F80')->equals(Color::fromHex('#FF8800')));
    }

    public function testItRejectsMalformedHex(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Color::fromHex('#GGG');
    }

    public function testItRejectsHexOfTheWrongLength(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Color::fromHex('#FFFFF');
    }

    public function testItRejectsOutOfRangeChannels(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('green');

        new Color(0, 256, 0);
    }

    public function testItRejectsNegativeChannels(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Color(-1, 0, 0);
    }

    public function testAlphaHelpers(): void
    {
        $this->assertTrue(Color::white()->isOpaque());
        $this->assertFalse(Color::white()->isTransparent());
        $this->assertTrue(Color::white()->withAlpha(0)->isTransparent());
        $this->assertFalse(Color::white()->withAlpha(128)->isOpaque());
    }

    /**
     * Any two fully transparent colours are the same colour, whatever RGB they
     * carry, because they resolve to the same bytes.
     */
    public function testTransparentColorsAreEqualRegardlessOfChannels(): void
    {
        $this->assertTrue(Color::rgba(255, 0, 0, 0)->equals(Color::rgba(0, 0, 255, 0)));
        $this->assertFalse(Color::white()->equals(Color::black()));
        $this->assertTrue(Color::grey(20)->equals(Color::rgb(20, 20, 20)));
    }
}
