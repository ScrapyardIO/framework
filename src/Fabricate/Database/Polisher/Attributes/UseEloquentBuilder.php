<?php

namespace Fabricate\Database\Polisher\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class UseEloquentBuilder
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string<\Fabricate\Database\Polisher\Builder>  $builderClass
     */
    public function __construct(public string $builderClass)
    {
    }
}
