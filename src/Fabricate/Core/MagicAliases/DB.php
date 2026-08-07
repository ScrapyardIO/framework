<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\MagicAliases\MagicAlias;

/** @see \Fabricate\Database\DatabaseManager */
class DB extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return 'db';
    }
}
