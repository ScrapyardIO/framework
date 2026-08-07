<?php

namespace Fabricate\Database\Polisher\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class CollectedBy
{
    /**
     * Create a new attribute instance.
     *
     * @param  class-string<\Fabricate\Database\Polisher\Collection<*, *>>  $collectionClass
     */
    public function __construct(public string $collectionClass)
    {
    }
}
