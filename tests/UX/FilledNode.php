<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\Enums\Damage;
use Fabricate\UX\Node;

/**
 * A node that fills its own bounds, so what it painted is exactly its geometry.
 *
 * Takes a packed int rather than a Color so the same fixture works on a mono and
 * an RGB565 surface without the fixture having to know which it is; Color's own
 * packing is pinned in ColorTest.
 *
 * Opacity is left off by default and opted into with {@see opaque()}, so a test
 * can choose whether this node is eligible to be a repaint root.
 */
class FilledNode extends Node
{
    public int $paint_count = 0;

    protected bool $opaque = false;

    public function __construct(
        int $x = 0,
        int $y = 0,
        int $width = 0,
        int $height = 0,
        protected int $color = 0xFFFF,
    ) {
        parent::__construct($x, $y, $width, $height);
    }

    public function paint(DrawingSurface $surface): void
    {
        $this->paint_count++;

        $surface->fill($this->color);
    }

    /**
     * Truthful, since this node really does fill every pixel of its bounds.
     */
    public function opaque(bool $opaque = true): static
    {
        $this->opaque = $opaque;

        return $this;
    }

    public function isOpaque(): bool
    {
        return $this->opaque;
    }

    public function color(): int
    {
        return $this->color;
    }

    /**
     * Exposed so damage tests can drive invalidation directly, which is protected
     * on Node because production code invalidates through typed setters.
     */
    public function touch(Damage $damage = Damage::PAINT): static
    {
        return $this->invalidate($damage);
    }
}
