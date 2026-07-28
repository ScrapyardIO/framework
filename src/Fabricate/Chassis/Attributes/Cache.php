<?php

namespace Fabricate\Chassis\Attributes;

use Attribute;
use Fabricate\Contracts\Chassis\Chassis;
use Fabricate\Contracts\Chassis\ContextualAttribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Cache implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public UnitEnum|string|null $store = null,
        public bool $memo = false,
    ) {
    }

    /**
     * Resolve the cache store.
     *
     * @param  self  $attribute
     * @param  \Fabricate\Contracts\Chassis\Chassis  $box
     * @return \Fabricate\Contracts\Cache\Repository
     */
    public static function resolve(self $attribute, Box $box)
    {
        return $attribute->memo
            ? $box->make('cache')->memo($attribute->store)
            : $box->make('cache')->store($attribute->store);
    }
}
