<?php

namespace Fabricate\Gfx;

use Microscrap\GFX\SDL3\SDL3Gfx;

class SDL3GfxRenderDriver extends GFXRenderDriver
{
    public function __construct()
    {
        parent::__construct(new SDL3Gfx);
    }
}
