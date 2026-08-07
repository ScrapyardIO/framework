<?php

namespace Fabricate\Database\Console\Migrations;

class TableGuesser
{
    /**
     * Attempt to guess the table name and "creation" status of the given migration.
     *
     * @param  string  $migration
     * @return array{string, bool}
     */
    public static function guess($migration)
    {
        foreach (MigrationTablePattern::cases() as $pattern) {
            if (preg_match($pattern->value, $migration, $matches)) {
                return [
                    $matches[$pattern->createsTable() ? 1 : 2],
                    $pattern->createsTable(),
                ];
            }
        }
    }
}
