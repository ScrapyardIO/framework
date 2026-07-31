<?php

namespace Fabricate\Framebuffers\DataObjects;

use Fabricate\NutsAndBolts\Geometry\Rect;

/**
 * The smallest region a surface can usefully transmit.
 *
 * Published by every framebuffer so callers can snap damage rectangles to real
 * transmit units instead of guessing. Sub-unit precision is wasted work: a
 * PageSegmentBuffer sends whole 8-row pages regardless, so a 3px-tall damage
 * rect and a 8px-tall one cost exactly the same bytes on the wire.
 */
final readonly class DamageGranularity
{
    public function __construct(
        public int $unit_width,
        public int $unit_height,
        public int $surface_width,
        public int $surface_height,
    ) {}

    /**
     * Arbitrary 1x1 damage, for buffers tracking individual dirty pixels.
     */
    public static function pixel(int $surface_width, int $surface_height): self
    {
        return new self(1, 1, $surface_width, $surface_height);
    }

    /**
     * Full-width horizontal bands, the shape vertical-page mono displays
     * (SSD1306 and friends) actually address.
     */
    public static function rows(int $rows, int $surface_width, int $surface_height): self
    {
        return new self($surface_width, $rows, $surface_width, $surface_height);
    }

    /**
     * All-or-nothing: any damage at all costs a full-surface transmit.
     */
    public static function wholeSurface(int $surface_width, int $surface_height): self
    {
        return new self($surface_width, $surface_height, $surface_width, $surface_height);
    }

    public function isPixelPerfect(): bool
    {
        return ($this->unit_width === 1) && ($this->unit_height === 1);
    }

    public function coversWholeSurface(): bool
    {
        return ($this->unit_width >= $this->surface_width)
            && ($this->unit_height >= $this->surface_height);
    }

    public function surfaceRect(): Rect
    {
        return new Rect(0, 0, $this->surface_width, $this->surface_height);
    }

    /**
     * Grow a damage rectangle out to unit boundaries and clamp it to the
     * surface, so a trailing partial band is not over-reported.
     */
    public function snap(Rect $rect): Rect
    {
        $surface = $this->surfaceRect();
        $clamped = $rect->intersect($surface);

        if ($clamped->isEmpty()) {
            return Rect::empty();
        }

        $left = intdiv($clamped->left(), $this->unit_width) * $this->unit_width;
        $top = intdiv($clamped->top(), $this->unit_height) * $this->unit_height;
        $right = ((intdiv($clamped->right(), $this->unit_width) + 1) * $this->unit_width) - 1;
        $bottom = ((intdiv($clamped->bottom(), $this->unit_height) + 1) * $this->unit_height) - 1;

        return Rect::fromBounds($left, $top, $right, $bottom)->intersect($surface);
    }
}
