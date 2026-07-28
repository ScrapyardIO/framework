<?php

namespace Fabricate\Contracts\Sketches\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Sketch
{
    /**
     * @param  non-empty-string  $name  Registration key used with SketchRegistry / workshop sketch.
     */
    public function __construct(public string $name) {}
}
