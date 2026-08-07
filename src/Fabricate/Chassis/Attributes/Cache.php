<?php

namespace Fabricate\Chassis\Attributes;

use Attribute;
use Fabricate\Chassis\Contracts\WireframeServiceContainer;
use Fabricate\Contracts\Chassis\ContextualAttribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Cache implements ContextualAttribute
{
    /**
     * Create a new class instance.
     *
     * @param UnitEnum|string|null $store
     * @param bool $memo
     */
    public function __construct(
        public UnitEnum|string|null $store = null,
        public bool $memo = false,
    ) {
    }

    /**
     * Resolve the cache store.
     *
     * @param self $attribute
     * @param WireframeServiceContainer $container
     * @return mixed
     */
    public static function resolve(self $attribute, WireframeServiceContainer $container): mixed
    {
        return $attribute->memo
            ? $container->make('cache')->memo($attribute->store)
            : $container->make('cache')->store($attribute->store);
    }
}
