<?php

namespace Fabricate\Contracts\Gfx;

use Fabricate\Gfx\GFXRenderDriver;

interface Factory
{
    public function engine(?string $engine = null): GFXRenderDriver;
}
