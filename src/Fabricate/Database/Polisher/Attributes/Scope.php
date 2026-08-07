<?php

namespace Fabricate\Database\Polisher\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Scope
{
    /**
     * Create a new attribute instance.
     */
    public function __construct()
    {
    }
}
