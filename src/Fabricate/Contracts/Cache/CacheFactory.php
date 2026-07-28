<?php

namespace Fabricate\Contracts\Cache;

use UnitEnum;

interface CacheFactory
{
    /**
     * Get a cache store instance by name.
     *
     * @param UnitEnum|string|null $name
     * @return Repository
     */
    public function store(UnitEnum|string|null $name = null): Repository;
}