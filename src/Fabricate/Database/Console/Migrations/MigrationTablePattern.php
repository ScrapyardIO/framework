<?php

namespace Fabricate\Database\Console\Migrations;

enum MigrationTablePattern: string
{
    case CREATE_TABLE = '/^create_(\w+)_table$/';
    case CREATE = '/^create_(\w+)$/';
    case CHANGE_TABLE = '/.+_(to|from|in)_(\w+)_table$/';
    case CHANGE = '/.+_(to|from|in)_(\w+)$/';

    public function createsTable(): bool
    {
        return $this === self::CREATE_TABLE || $this === self::CREATE;
    }
}
