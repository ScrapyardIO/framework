<?php

namespace Fabricate\Contracts\Rendering;

use Fabricate\Contracts\Rendering\GFXRenderDriver;

interface RenderFactory
{
    public function engine(?string $engine = null): GFXRenderDriver;
}
