<?php

namespace Fabricate\Gfx;

use Microscrap\GFX\GLFW\GLFWGfx;

class GlfwGfxRenderDriver extends GFXRenderDriver
{
    public function __construct()
    {
        parent::__construct(new GLFWGfx);
    }
}
