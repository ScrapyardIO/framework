<?php

namespace Fabricate\UX;

use Fabricate\Contracts\Framebuffers\Enums\BitDepth;
use Fabricate\Contracts\Framebuffers\Enums\PixelFormat;
use Fabricate\Framebuffers\FormatSpec;
use InvalidArgumentException;

/**
 * A colour expressed once and resolved per surface.
 *
 * Nodes must not care whether they are painting to an SSD1306 or an RGBA window,
 * but every renderer primitive takes a packed int in the surface's native depth.
 * So the canonical form here is 8-bit-per-channel RGBA and {@see resolveFor()}
 * does the packing.
 *
 * The packings deliberately agree with the ink
 * {@see \Fabricate\Core\VisualPresentation::defaultTextColor()} already picks for
 * each depth, so a white Color and the existing default white are the same bytes.
 */
final readonly class Color
{
    public function __construct(
        public int $red,
        public int $green,
        public int $blue,
        public int $alpha = 255,
    ) {
        foreach (['red' => $red, 'green' => $green, 'blue' => $blue, 'alpha' => $alpha] as $channel => $value) {
            if (($value < 0) || ($value > 255)) {
                throw new InvalidArgumentException("Colour channel {$channel} must be 0-255, got {$value}.");
            }
        }
    }

    public static function rgb(int $red, int $green, int $blue): self
    {
        return new self($red, $green, $blue);
    }

    public static function rgba(int $red, int $green, int $blue, int $alpha): self
    {
        return new self($red, $green, $blue, $alpha);
    }

    public static function white(): self
    {
        return new self(255, 255, 255);
    }

    public static function black(): self
    {
        return new self(0, 0, 0);
    }

    /**
     * Fully transparent, which every depth resolves to 0 — the "no ink" value
     * that also reads as an unlit monochrome pixel.
     */
    public static function transparent(): self
    {
        return new self(0, 0, 0, 0);
    }

    public static function grey(int $level): self
    {
        return new self($level, $level, $level);
    }

    /**
     * Accepts #RGB, #RGBA, #RRGGBB and #RRGGBBAA, with or without the hash.
     */
    public static function fromHex(string $hex): self
    {
        $digits = ltrim($hex, '#');

        if (! ctype_xdigit($digits)) {
            throw new InvalidArgumentException("Colour hex must be hexadecimal, got '{$hex}'.");
        }

        // Expand shorthand by doubling each digit, so #F0A === #FF00AA.
        if ((strlen($digits) === 3) || (strlen($digits) === 4)) {
            $digits = implode('', array_map(fn (string $digit): string => $digit.$digit, str_split($digits)));
        }

        return match (strlen($digits)) {
            6 => new self(
                (int) hexdec(substr($digits, 0, 2)),
                (int) hexdec(substr($digits, 2, 2)),
                (int) hexdec(substr($digits, 4, 2)),
            ),
            8 => new self(
                (int) hexdec(substr($digits, 0, 2)),
                (int) hexdec(substr($digits, 2, 2)),
                (int) hexdec(substr($digits, 4, 2)),
                (int) hexdec(substr($digits, 6, 2)),
            ),
            default => throw new InvalidArgumentException("Colour hex must be 3, 4, 6 or 8 digits, got '{$hex}'."),
        };
    }

    public function isTransparent(): bool
    {
        return $this->alpha === 0;
    }

    public function isOpaque(): bool
    {
        return $this->alpha === 255;
    }

    public function withAlpha(int $alpha): self
    {
        return new self($this->red, $this->green, $this->blue, $alpha);
    }

    /**
     * The plain 0xRRGGBB word, for the hardware that is not a display: an
     * addressable LED, a driver register, a protocol that predates alpha.
     *
     * Distinct from {@see resolveFor()}, which answers "what int does this
     * surface want"; this answers "what colour is this", full stop.
     */
    public function toRgb(): int
    {
        return ($this->red << 16) | ($this->green << 8) | $this->blue;
    }

    /**
     * Pack into the int a renderer primitive expects for this surface.
     *
     * Deliberately a mirror of {@see \Microscrap\GFX\SDL3\Sdl3Framebuffer::unmapColor()}
     * rather than a second convention: that method is the proven RGBA-to-packed
     * path in this stack, and two disagreeing conventions would mean a colour
     * resolved by a node and the same colour read back off a surface were not the
     * same colour.
     *
     * Every {@see BitDepth} is handled explicitly and there is no default arm, so
     * adding a depth to the enum forces a decision here rather than silently
     * falling through.
     */
    public function resolveFor(FormatSpec $spec): int
    {
        if ($this->isTransparent()) {
            return 0;
        }

        if ($this->isMonochrome($spec)) {
            return $this->lightsAMonochromePixel() ? 1 : 0;
        }

        return match ($spec->bit_depth) {
            // A monochrome depth is always caught above; reaching here means a
            // 1-bit surface that did not declare a mono pixel format.
            BitDepth::B1 => $this->lightsAMonochromePixel() ? 1 : 0,
            BitDepth::B8 => $this->red,
            BitDepth::B10, BitDepth::B24 => ($this->red << 16) | ($this->green << 8) | $this->blue,
            BitDepth::B12 => $this->packRgb444(),
            BitDepth::B16 => $this->packRgb565(),
            BitDepth::B18 => $this->packRgb666(),
            BitDepth::B32 => ($this->red << 24) | ($this->green << 16) | ($this->blue << 8) | $this->alpha,
        };
    }

    /**
     * Whether this colour lights a monochrome pixel.
     *
     * Any channel above half scale is enough, matching the existing surface
     * convention. Note this is *not* a luminance test: saturated red lights a
     * pixel here, where a luma rule would leave it dark.
     */
    public function lightsAMonochromePixel(): bool
    {
        return ($this->red > 127) || ($this->green > 127) || ($this->blue > 127);
    }

    /**
     * Mono is a property of the pixel format as well as the depth, so a paged
     * SSD1306 layout counts even when the depth is read from elsewhere.
     */
    protected function isMonochrome(FormatSpec $spec): bool
    {
        return ($spec->bit_depth === BitDepth::B1)
            || ($spec->pixel_format === PixelFormat::MONO_VERTICAL_PAGE)
            || ($spec->pixel_format === PixelFormat::MONO_HORIZONTAL);
    }

    public function equals(self $other): bool
    {
        if ($this->isTransparent() && $other->isTransparent()) {
            return true;
        }

        return ($this->red === $other->red)
            && ($this->green === $other->green)
            && ($this->blue === $other->blue)
            && ($this->alpha === $other->alpha);
    }

    protected function packRgb444(): int
    {
        return (($this->red >> 4) << 8) | (($this->green >> 4) << 4) | ($this->blue >> 4);
    }

    protected function packRgb565(): int
    {
        return (($this->red >> 3) << 11) | (($this->green >> 2) << 5) | ($this->blue >> 3);
    }

    /**
     * Left-justified RGB666: each 6-bit channel sits in the high bits of its own
     * byte, which is what the 3-byte MSB slicing in the packers expects.
     */
    protected function packRgb666(): int
    {
        return (($this->red & 0xFC) << 16) | (($this->green & 0xFC) << 8) | ($this->blue & 0xFC);
    }
}
