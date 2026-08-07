<?php

use Fabricate\Graph\Database\Neo4jConnection;

if (! function_exists('cypher')) {
    /**
     * Run a Cypher query on the default neo4j connection.
     *
     * @param  array<string, mixed>  $bindings
     * @return array<int, array<string, mixed>>
     */
    function cypher(string $query, array $bindings = [], ?string $connection = null): array
    {
        return neo4j_connection($connection)->select($query, $bindings);
    }
}

if (! function_exists('cypher_one')) {
    /**
     * Run a Cypher query and return the first record.
     *
     * @param  array<string, mixed>  $bindings
     */
    function cypher_one(string $query, array $bindings = [], ?string $connection = null): mixed
    {
        return neo4j_connection($connection)->selectOne($query, $bindings);
    }
}

if (! function_exists('cypher_run')) {
    /**
     * Run a Cypher statement (write) on the neo4j connection.
     *
     * @param  array<string, mixed>  $bindings
     */
    function cypher_run(string $query, array $bindings = [], ?string $connection = null): bool
    {
        return neo4j_connection($connection)->statement($query, $bindings);
    }
}

if (! function_exists('neo4j_connection')) {
    /**
     * Resolve a Neo4j database connection.
     */
    function neo4j_connection(?string $name = null): Neo4jConnection
    {
        $name = $name ?? config('database.default');
        $connection = app('db')->connection($name);

        if (! $connection instanceof Neo4jConnection) {
            $connection = app('db')->connection('neo4j');
        }

        if (! $connection instanceof Neo4jConnection) {
            throw new \RuntimeException(
                'No Neo4j connection available. Register Fabricate\\Graph\\GraphServiceProvider and configure database.connections.neo4j.'
            );
        }

        return $connection;
    }
}
