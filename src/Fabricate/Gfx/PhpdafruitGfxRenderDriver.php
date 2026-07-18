<?php

namespace Fabricate\Gfx;

use Microscrap\GFX\PhpdaFruit\PhpdafruitGfx;

class PhpdafruitGfxRenderDriver extends GFXRenderDriver
{
    public function __construct()
    {
        parent::__construct(new PhpdafruitGfx);
    }
}
