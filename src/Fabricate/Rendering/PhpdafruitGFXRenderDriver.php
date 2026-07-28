<?php

namespace Fabricate\Rendering;

use Microscrap\GFX\PhpdaFruit\PhpdafruitGfx;

class PhpdafruitGFXRenderDriver extends GFXRenderDriver
{
    public function __construct()
    {
        parent::__construct(new PhpdafruitGfx);
    }
}
