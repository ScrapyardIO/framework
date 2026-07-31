<?php

namespace Fabricate\UX;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\NutsAndBolts\Geometry\Alignment;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;

/**
 * The measured extent of a run of text, plus the cursor correction that turns a
 * *visual* box position into the cursor position the renderer wants.
 *
 * Custom GFX fonts report bounds relative to the baseline cursor, not the top
 * left of the ink: `getTextBounds()` hands back an `x1`/`y1` that is usually
 * negative. Placing text by feeding the box position straight to `setCursor()`
 * is therefore wrong by a font-dependent amount, which is why every sketch in
 * this repo open-codes the same `- $bounds['x1']` correction. It lives here
 * once instead, so intrinsic text sizing and text centring cannot disagree.
 */
final readonly class TextMetrics
{
    /**
     * @param  Size  $size  the visual extent of the ink
     * @param  Point  $offset  added to a visual position to get the cursor position
     */
    public function __construct(
        public Size $size,
        public Point $offset,
    ) {}

    /**
     * Measure $text with whatever font, size and wrapping $surface currently has
     * set. Callers configure the surface first, exactly as they would before
     * printing.
     */
    public static function of(DrawingSurface $surface, string $text): self
    {
        $bounds = $surface->getTextBounds($text, 0, 0);

        return new self(
            new Size($bounds['w'], $bounds['h']),
            new Point(-$bounds['x1'], -$bounds['y1']),
        );
    }

    /**
     * Where to put the cursor so the ink lands aligned inside $box.
     */
    public function cursorIn(Rect $box, ?Alignment $alignment = null): Point
    {
        $placed = ($alignment ?? Alignment::center())->positionIn($box, $this->size);

        return new Point($placed->x + $this->offset->x, $placed->y + $this->offset->y);
    }
}
