<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\Rendering\DrawingSurface;
use Fabricate\Contracts\UX\Enums\Damage;
use Fabricate\UX\Node;

/**
 * A node that paints nothing itself, for testing tree plumbing without ink.
 */
class EmptyNode extends Node
{
    public int $paint_count = 0;

    public int $mount_count = 0;

    public function paint(DrawingSurface $surface): void
    {
        $this->paint_count++;
    }

    public function mount(): void
    {
        $this->mount_count++;
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
