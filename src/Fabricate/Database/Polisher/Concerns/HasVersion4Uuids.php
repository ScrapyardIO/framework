<?php

namespace Fabricate\Database\Polisher\Concerns;

use Fabricate\NutsAndBolts\Str;

trait HasVersion4Uuids
{
    use HasUuids;

    /**
     * Generate a new UUID (version 4) for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        return (string) Str::orderedUuid();
    }
}
