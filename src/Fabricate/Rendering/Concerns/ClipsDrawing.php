<?php

namespace Fabricate\Rendering\Concerns;

use Fabricate\NutsAndBolts\Geometry\Rect;

/**
 * A stack of clip rectangles shared by every Renderer2D implementation.
 *
 * The stack only holds state and answers two questions — {@see clipAllows()}
 * for single pixels and {@see clipSegment()} for rectangles. Each driver calls
 * them from its own pixel funnel, so the arithmetic lives here once instead of
 * being reimplemented per backend.
 *
 * Clipping is a throughput concern as much as a visual one: a pixel that
 * escapes the clip and reaches a dirty-tracking framebuffer marks a region
 * dirty, and one spurious dirty page on an I2C OLED costs a real 20-30ms
 * transmit. Drivers must therefore consult the clip *before* writing, never
 * paint-and-repair afterwards.
 */
trait ClipsDrawing
{
    /**
     * Pushed regions, already intersected with their parent.
     *
     * @var array<int, Rect>
     */
    protected array $clip_stack = [];

    /**
     * Restrict drawing to $rect, intersected with any clip already active, so a
     * child region can never widen its parent's.
     */
    public function pushClip(Rect $rect): static
    {
        $active = $this->clip();

        $this->clip_stack[] = is_null($active) ? $rect : $active->intersect($rect);

        return $this;
    }

    public function popClip(): static
    {
        array_pop($this->clip_stack);

        return $this;
    }

    /**
     * The active clip, or null when drawing is unrestricted.
     */
    public function clip(): ?Rect
    {
        if ($this->clip_stack === []) {
            return null;
        }

        return $this->clip_stack[count($this->clip_stack) - 1];
    }

    /**
     * Run $callback with $rect clipped in, popping even if it throws so an
     * exception mid-paint cannot leave the stack unbalanced.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function withClip(Rect $rect, callable $callback): mixed
    {
        $this->pushClip($rect);

        try {
            return $callback();
        } finally {
            $this->popClip();
        }
    }

    public function clearClips(): static
    {
        $this->clip_stack = [];

        return $this;
    }

    /**
     * True when a single pixel may be written. Unclipped renderers short-circuit
     * on the empty stack, keeping the no-clip path free.
     */
    protected function clipAllows(int $x, int $y): bool
    {
        $clip = $this->clip();

        if (is_null($clip)) {
            return true;
        }

        return $clip->contains($x, $y);
    }

    /**
     * Intersect a rectangle with the active clip, returning null when nothing
     * survives. Rectangles are clipped analytically and stay a single fill —
     * degrading to per-pixel writes here would regress every filled rect in the
     * system, since the segment path is the documented fast path.
     */
    protected function clipSegment(int $x, int $y, int $width, int $height): ?Rect
    {
        if (($width <= 0) || ($height <= 0)) {
            return null;
        }

        $rect = new Rect($x, $y, $width, $height);
        $clip = $this->clip();

        if (is_null($clip)) {
            return $rect;
        }

        $clipped = $rect->intersect($clip);

        return $clipped->isEmpty() ? null : $clipped;
    }
}
