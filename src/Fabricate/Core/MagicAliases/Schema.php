<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\Database\Schema\Builder;
use Fabricate\MagicAliases\MagicAlias;

/**
 * @method static void create(string $table, \Closure $callback)
 * @method static void dropIfExists(string $table)
 * @method static void table(string $table, \Closure $callback)
 *
 * @see \Fabricate\Database\Schema\Builder
 */
class Schema extends MagicAlias
{
    protected static bool $cached = false;

    protected static function getMagicAliasAccessor(): string
    {
        return 'db.schema';
    }

    /**
     * Get a schema builder instance for a connection.
     */
    public static function connection(?string $name = null): Builder
    {
        return static::getMagicAliasApplication()['db']->connection($name)->getSchemaBuilder();
    }
}
