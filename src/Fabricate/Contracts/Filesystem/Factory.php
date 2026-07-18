<?php

namespace Fabricate\Contracts\Filesystem;

use UnitEnum;

interface Factory
{
    /**
     * Get a filesystem implementation.
     *
     * @param UnitEnum|string|null $name
     */
    public function disk(UnitEnum|string|null $name = null);
}
