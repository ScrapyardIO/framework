<?php

namespace Fabricate\Contracts\Framebuffers\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsFramebuffer
{
    /**
     * @param  non-empty-string  $name  Registration key used with FramebufferManager::extend() / make().
     */
    public function __construct(public string $name) {}
}
