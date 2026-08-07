<?php

namespace Fabricate\Database\Events;

use Fabricate\Contracts\Database\Events\MigrationEvent as MigrationEventContract;
use Fabricate\Database\Migrations\Migration;

abstract class MigrationEvent implements MigrationEventContract
{
    /**
     * A migration instance.
     *
     * @var \Fabricate\Database\Migrations\Migration
     */
    public $migration;

    /**
     * The migration method that was called.
     *
     * @var string
     */
    public $method;

    /**
     * Create a new event instance.
     *
     * @param  \Fabricate\Database\Migrations\Migration  $migration
     * @param  string  $method
     */
    public function __construct(Migration $migration, $method)
    {
        $this->method = $method;
        $this->migration = $migration;
    }
}
