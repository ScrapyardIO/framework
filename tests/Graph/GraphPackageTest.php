<?php

use Fabricate\Core\DefaultProviders;
use Fabricate\Core\Machine;
use Fabricate\Database\Connectors\ConnectionFactory;
use Fabricate\Database\DatabaseManager;
use Fabricate\Database\Polisher\Model as SqlPolisherModel;
use Fabricate\Graph\Database\Connectors\Neo4jConnector;
use Fabricate\Graph\Database\Neo4jConnection;
use Fabricate\Graph\GraphServiceProvider;
use Fabricate\Graph\Polisher\Model as GraphModel;
use Fabricate\NutsAndBolts\ServiceProvider;

test('graph package exposes neo4j connector and graph polisher model', function () {
    expect(class_exists(Neo4jConnector::class))->toBeTrue()
        ->and(class_exists(Neo4jConnection::class))->toBeTrue()
        ->and(is_subclass_of(GraphModel::class, SqlPolisherModel::class))->toBeTrue()
        ->and(function_exists('cypher'))->toBeTrue()
        ->and(function_exists('cypher_one'))->toBeTrue()
        ->and(function_exists('cypher_run'))->toBeTrue()
        ->and(function_exists('neo4j_connection'))->toBeTrue();
});

test('graph service provider is optional and not a default provider', function () {
    expect(DefaultProviders::make()->toArray())->not->toContain(GraphServiceProvider::class)
        ->and(is_subclass_of(GraphServiceProvider::class, ServiceProvider::class))->toBeTrue();
});

test('graph service provider registers neo4j database extension', function () {
    $app = new Machine(sys_get_temp_dir());

    $app->singleton('db.factory', fn ($app) => new ConnectionFactory($app));
    $app->singleton('db', fn ($app) => new DatabaseManager($app, $app['db.factory']));
    $app->instance('config', new class
    {
        public function get($key, $default = null)
        {
            return $default;
        }

        public function offsetGet($offset): mixed
        {
            return null;
        }

        public function offsetExists($offset): bool
        {
            return false;
        }

        public function offsetSet($offset, $value): void
        {
            //
        }

        public function offsetUnset($offset): void
        {
            //
        }
    });

    $app->register(GraphServiceProvider::class);

    $db = $app->make('db');

    $extensions = (new ReflectionClass($db))->getProperty('extensions');
    $extensions->setAccessible(true);

    expect($extensions->getValue($db))->toHaveKey('neo4j')
        ->and($app->bound('db.connector.neo4j'))->toBeTrue()
        ->and($app->make('db.connector.neo4j'))->toBeInstanceOf(Neo4jConnector::class);
});

test('neo4j connector builds bolt uri from config', function () {
    $connector = new Neo4jConnector;
    $method = new ReflectionMethod($connector, 'buildConnectionUri');
    $method->setAccessible(true);

    $uri = $method->invoke($connector, [
        'scheme' => 'bolt',
        'host' => 'graph.local',
        'port' => 7687,
        'database' => 'neo4j',
    ]);

    expect($uri)->toBe('bolt://graph.local:7687?database=neo4j');
});
