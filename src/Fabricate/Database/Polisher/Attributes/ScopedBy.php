<?php

namespace Fabricate\Database\Polisher\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ScopedBy
{
    /**
     * Create a new attribute instance.
     *
     * @param  array|string  $classes
     */
    public function __construct(public array|string $classes)
    {
    }
}
