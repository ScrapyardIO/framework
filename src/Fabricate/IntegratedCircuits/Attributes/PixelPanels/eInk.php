<?php

namespace Fabricate\IntegratedCircuits\Attributes\PixelPanels;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class eInk
{
    public function __construct(
        public readonly int $color_count
    ) {}
}
