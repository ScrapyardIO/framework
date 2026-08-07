<?php

namespace Fabricate\Database\Polisher\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class UseFactory
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string<\Fabricate\Database\Polisher\Factories\Factory>  $factoryClass
     */
    public function __construct(public string $factoryClass)
    {
    }
}
