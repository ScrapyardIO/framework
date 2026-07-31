<?php

namespace Fabricate\UX;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\NutsAndBolts\Geometry\Point;
use Fabricate\NutsAndBolts\Geometry\Rect;
use Fabricate\NutsAndBolts\Geometry\Size;
use Fabricate\Rendering\Renderer2D;

/**
 * A node's private view of the renderer: origin at its own top-left, and unable
 * to paint outside its own bounds.
 *
 * Translation happens here at the API boundary rather than down in the driver
 * pixel funnels, which mirrors how {@see \Fabricate\Core\VisualPresentation}
 * already proxies every {@see DrawingSurface} call through to the renderer. The
 * payoff is coherence: {@see getTextBounds()} can hand back *local* coordinates,
 * which a translation applied to rasterised output could not do.
 *
 * Containment is enforced by the Slice 1 clip stack, not by trusting nodes to
 * respect their bounds — {@see paint()} pushes this surface's clip for the whole
 * duration of a node's painting.
 */
final class Surface implements DrawingSurface
{
    /**
     * @param  Renderer2D  $renderer  the shared target every node ultimately draws into
     * @param  int  $offset_x  added to local coordinates to reach renderer space
     * @param  Rect  $clip  in renderer space, already intersected with every ancestor
     * @param  Size  $size  this surface's own extent, in local space
     */
    public function __construct(
        protected readonly Renderer2D $renderer,
        protected readonly int $offset_x,
        protected readonly int $offset_y,
        protected readonly Rect $clip,
        protected readonly Size $size,
    ) {}

    /**
     * The root surface for a whole renderer, origin at 0,0 and clipped to the
     * full extent.
     */
    public static function root(Renderer2D $renderer, int $width, int $height): self
    {
        return new self($renderer, 0, 0, new Rect(0, 0, $width, $height), new Size($width, $height));
    }

    /**
     * The surface for a node already positioned in renderer space, clipped to
     * $clip narrowed by the node's own bounds.
     *
     * This is how a stage starts painting partway down a tree, at the nearest
     * opaque ancestor of a damaged area, instead of always descending from the
     * root.
     */
    public static function forNode(Renderer2D $renderer, Rect $global, Rect $clip): self
    {
        return new self(
            $renderer,
            $global->x,
            $global->y,
            $clip->intersect($global),
            new Size($global->width, $global->height),
        );
    }

    /**
     * Derive the surface for a child occupying $local within this one.
     *
     * The child's clip is intersected with this one rather than replacing it, so
     * a child can only ever shrink the paintable area — that is what stops a
     * deeply nested node from escaping an ancestor's bounds.
     */
    public function forChild(Rect $local): self
    {
        $global = $local->translate($this->offset_x, $this->offset_y);

        return new self(
            $this->renderer,
            $this->offset_x + $local->x,
            $this->offset_y + $local->y,
            $this->clip->intersect($global),
            new Size($local->width, $local->height),
        );
    }

    /**
     * Run $painter with this surface's clip active, restoring the previous clip
     * afterwards even if the painter throws.
     */
    public function paint(callable $painter): mixed
    {
        return $this->renderer->withClip($this->clip, $painter);
    }

    public function size(): Size
    {
        return $this->size;
    }

    /**
     * This surface's own extent in local space, so a node can paint edge to edge
     * without knowing where it sits.
     */
    public function bounds(): Rect
    {
        return $this->size->atOrigin();
    }

    /**
     * Where this surface's origin sits in renderer space.
     */
    public function origin(): Point
    {
        return new Point($this->offset_x, $this->offset_y);
    }

    /**
     * The effective clip in renderer space, already narrowed by every ancestor.
     */
    public function clip(): Rect
    {
        return $this->clip;
    }

    /**
     * True when nothing painted here could possibly land on the surface, letting
     * a caller skip a subtree entirely.
     */
    public function isFullyClipped(): bool
    {
        return $this->clip->isEmpty();
    }

    /**
     * Map a local point into renderer space.
     */
    public function toGlobal(int $x, int $y): Point
    {
        return new Point($x + $this->offset_x, $y + $this->offset_y);
    }

    public function drawPixel(int $x, int $y, int $color): static
    {
        $this->renderer->drawPixel($x + $this->offset_x, $y + $this->offset_y, $color);

        return $this;
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: int}>  $pixels
     */
    public function drawPixels(array $pixels): static
    {
        $translated = [];

        foreach ($pixels as [$x, $y, $color]) {
            $translated[] = [$x + $this->offset_x, $y + $this->offset_y, $color];
        }

        $this->renderer->drawPixels($translated);

        return $this;
    }

    public function drawLine(int $x0, int $y0, int $x1, int $y1, int $color): static
    {
        $this->renderer->drawLine(
            $x0 + $this->offset_x,
            $y0 + $this->offset_y,
            $x1 + $this->offset_x,
            $y1 + $this->offset_y,
            $color,
        );

        return $this;
    }

    public function drawHorizontalLine(int $x, int $y, int $w, int $color): static
    {
        $this->renderer->drawHorizontalLine($x + $this->offset_x, $y + $this->offset_y, $w, $color);

        return $this;
    }

    public function drawVerticalLine(int $x, int $y, int $h, int $color): static
    {
        $this->renderer->drawVerticalLine($x + $this->offset_x, $y + $this->offset_y, $h, $color);

        return $this;
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: int, 3: int, 4: int}>  $lines
     */
    public function drawLines(array $lines): static
    {
        $translated = [];

        foreach ($lines as [$x0, $y0, $x1, $y1, $color]) {
            $translated[] = [
                $x0 + $this->offset_x,
                $y0 + $this->offset_y,
                $x1 + $this->offset_x,
                $y1 + $this->offset_y,
                $color,
            ];
        }

        $this->renderer->drawLines($translated);

        return $this;
    }

    public function drawRect(int $x, int $y, int $w, int $h, int $color): static
    {
        $this->renderer->drawRect($x + $this->offset_x, $y + $this->offset_y, $w, $h, $color);

        return $this;
    }

    public function fillRect(int $x, int $y, int $w, int $h, int $color): static
    {
        $this->renderer->fillRect($x + $this->offset_x, $y + $this->offset_y, $w, $h, $color);

        return $this;
    }

    public function drawRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static
    {
        $this->renderer->drawRoundRect($x + $this->offset_x, $y + $this->offset_y, $w, $h, $r, $color);

        return $this;
    }

    public function fillRoundRect(int $x, int $y, int $w, int $h, int $r, int $color): static
    {
        $this->renderer->fillRoundRect($x + $this->offset_x, $y + $this->offset_y, $w, $h, $r, $color);

        return $this;
    }

    public function drawCircle(int $x0, int $y0, int $r, int $color): static
    {
        $this->renderer->drawCircle($x0 + $this->offset_x, $y0 + $this->offset_y, $r, $color);

        return $this;
    }

    public function fillCircle(int $x0, int $y0, int $r, int $color): static
    {
        $this->renderer->fillCircle($x0 + $this->offset_x, $y0 + $this->offset_y, $r, $color);

        return $this;
    }

    public function drawEllipse(int $x0, int $y0, int $rw, int $rh, int $color): static
    {
        $this->renderer->drawEllipse($x0 + $this->offset_x, $y0 + $this->offset_y, $rw, $rh, $color);

        return $this;
    }

    public function fillEllipse(int $x0, int $y0, int $rw, int $rh, int $color): static
    {
        $this->renderer->fillEllipse($x0 + $this->offset_x, $y0 + $this->offset_y, $rw, $rh, $color);

        return $this;
    }

    public function drawTriangle(int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $color): static
    {
        $this->renderer->drawTriangle(
            $x0 + $this->offset_x,
            $y0 + $this->offset_y,
            $x1 + $this->offset_x,
            $y1 + $this->offset_y,
            $x2 + $this->offset_x,
            $y2 + $this->offset_y,
            $color,
        );

        return $this;
    }

    public function fillTriangle(int $x0, int $y0, int $x1, int $y1, int $x2, int $y2, int $color): static
    {
        $this->renderer->fillTriangle(
            $x0 + $this->offset_x,
            $y0 + $this->offset_y,
            $x1 + $this->offset_x,
            $y1 + $this->offset_y,
            $x2 + $this->offset_x,
            $y2 + $this->offset_y,
            $color,
        );

        return $this;
    }

    /**
     * Fills *this surface*, not the screen.
     *
     * The renderer's own fill() means the whole output, which is never what a
     * node means by "fill me", so this becomes a bounded rect instead.
     */
    public function fill(int $color): static
    {
        return $this->fillRect(0, 0, $this->size->width, $this->size->height, $color);
    }

    public function setCursor(int $x, int $y): static
    {
        $this->renderer->setCursor($x + $this->offset_x, $y + $this->offset_y);

        return $this;
    }

    public function setTextSize(int $s, ?int $y = null): static
    {
        $this->renderer->setTextSize($s, $y);

        return $this;
    }

    public function setTextColor(int $color, ?int $bg = null): static
    {
        $this->renderer->setTextColor($color, $bg);

        return $this;
    }

    public function setTextWrap(bool $wrap): static
    {
        $this->renderer->setTextWrap($wrap);

        return $this;
    }

    public function setFont(object|string|null $f = null): static
    {
        $this->renderer->setFont($f);

        return $this;
    }

    public function setCp437(bool $enable): static
    {
        $this->renderer->setCp437($enable);

        return $this;
    }

    public function write(int $c): static
    {
        $this->renderer->write($c);

        return $this;
    }

    public function drawChar(int $x, int $y, int $c, int $color, int $bg, int $size_x, int $size_y): static
    {
        $this->renderer->drawChar($x + $this->offset_x, $y + $this->offset_y, $c, $color, $bg, $size_x, $size_y);

        return $this;
    }

    public function print(string $str): static
    {
        $this->renderer->print($str);

        return $this;
    }

    public function println(string $str = ''): static
    {
        $this->renderer->println($str);

        return $this;
    }

    /**
     * Local coordinates in *and* out, so a node can measure text and then lay it
     * out without ever seeing a renderer-space number.
     *
     * @return array{x1: int, y1: int, w: int, h: int}
     */
    public function getTextBounds(string $str, int $x, int $y): array
    {
        $bounds = $this->renderer->getTextBounds($str, $x + $this->offset_x, $y + $this->offset_y);

        return [
            'x1' => $bounds['x1'] - $this->offset_x,
            'y1' => $bounds['y1'] - $this->offset_y,
            'w' => $bounds['w'],
            'h' => $bounds['h'],
        ];
    }
}
