<?php

namespace Fabricate\Rendering;

use Fabricate\Contracts\Rendering\GFXRenderDriver as DriverContract;

abstract class GFXRenderDriver implements DriverContract
{
    public function __construct(
        public readonly Renderer $renderer
    ) {}
}
