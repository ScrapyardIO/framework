<?php

namespace Fabricate\Gfx;

use Fabricate\Contracts\Gfx\GFXRenderDriver as DriverContract;

abstract class GFXRenderDriver implements DriverContract
{
    public function __construct(
        public readonly Renderer $renderer
    ) {}
}
