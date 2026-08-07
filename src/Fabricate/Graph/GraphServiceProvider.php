<?php

namespace Fabricate\Graph;

use Fabricate\Graph\Console\GraphModelMakeCommand;
use Fabricate\Graph\Database\Connectors\Neo4jConnector;
use Fabricate\Graph\Database\Neo4jConnection;
use Fabricate\NutsAndBolts\ServiceProvider;

/**
 * Optional companion provider — not registered in Core DefaultProviders.
 *
 * Apps that need Neo4j should register this provider and add a
 * `database.connections.neo4j` entry (see config/neo4j.php).
 */
class GraphServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->bind('db.connector.neo4j', fn () => new Neo4jConnector);

        $this->container->resolving('db', function ($db) {
            $db->extend('neo4j', function (array $config, string $name) {
                $config['name'] = $name;

                $client = (new Neo4jConnector)->connect($config);

                return new Neo4jConnection(
                    $client,
                    $config['database'] ?? '',
                    $config['prefix'] ?? '',
                    $config
                );
            });
        });

        $this->commands([
            GraphModelMakeCommand::class,
        ]);
    }

    public function boot(): void
    {
        if ($this->container->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/neo4j.php' => $this->container->configPath('neo4j.php'),
            ], 'fabricate-graph-config');
        }
    }
}
