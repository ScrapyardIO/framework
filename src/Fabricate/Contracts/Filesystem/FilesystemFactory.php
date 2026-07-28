<?php

namespace Fabricate\Contracts\Filesystem;

use UnitEnum;

interface FilesystemFactory
{
    /**
     * Get a filesystem implementation.
     *
     * @param UnitEnum|string|null $name
     */
    public function disk(UnitEnum|string|null $name = null);
}
