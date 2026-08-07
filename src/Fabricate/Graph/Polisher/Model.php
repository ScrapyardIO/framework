<?php

namespace Fabricate\Graph\Polisher;

use Fabricate\Database\Polisher\Model as PolisherModel;
use Fabricate\Graph\Database\Neo4jConnection;
use RuntimeException;

/**
 * Base Polisher model for Neo4j graph nodes (companion to SQL Polisher).
 *
 * Models should set $connection to a neo4j database connection name and
 * treat $table as the primary Neo4j label.
 */
abstract class Model extends PolisherModel
{
    /**
     * Default connection name when config provides a neo4j connection.
     *
     * @var string|null
     */
    protected $connection = 'neo4j';

    /**
     * Primary key property on Neo4j nodes.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Neo4j node keys are typically UUIDs / strings.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * Ensure the resolved connection is a Neo4j graph connection.
     */
    public function getConnection(): Neo4jConnection
    {
        $connection = parent::getConnection();

        if (! $connection instanceof Neo4jConnection) {
            throw new RuntimeException(
                'Graph Polisher models require a neo4j connection (Fabricate\\Graph). Register GraphServiceProvider and configure database.connections.neo4j.'
            );
        }

        return $connection;
    }

    /**
     * Label used for Cypher MATCH/CREATE (maps to Eloquent/Polisher "table").
     */
    public function getLabel(): string
    {
        return $this->getTable();
    }
}
