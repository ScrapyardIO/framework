<?php

namespace Fabricate\Graph\Database\Connectors;

use InvalidArgumentException;
use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\AuthenticateInterface;
use Laudis\Neo4j\Contracts\ClientInterface;

class Neo4jConnector
{
    /**
     * Establish a Neo4j client connection.
     *
     * @param  array<string, mixed>  $config
     */
    public function connect(array $config): ClientInterface
    {
        $uri = $this->buildConnectionUri($config);
        $auth = $this->buildAuthentication($config);

        return ClientBuilder::create()
            ->withDriver('neo4j_driver', $uri, $auth)
            ->build();
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function buildConnectionUri(array $config): string
    {
        $scheme = $config['scheme'] ?? 'bolt';
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 7687;

        $database = null;
        if (! empty($config['prefix'])) {
            $database = $config['prefix'];
        } elseif (! empty($config['database']) && $config['database'] !== 'default') {
            $database = $config['database'];
        }

        if (! in_array($scheme, ['bolt', 'bolt+s', 'bolt+ssc', 'neo4j', 'neo4j+s', 'neo4j+ssc'], true)) {
            throw new InvalidArgumentException("Unsupported Neo4j scheme: {$scheme}");
        }

        $uri = "{$scheme}://{$host}:{$port}";

        if ($database) {
            $uri .= '?database='.urlencode($database);
        }

        return $uri;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function buildAuthentication(array $config): AuthenticateInterface
    {
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;
        $token = $config['token'] ?? null;

        if ($token) {
            return Authenticate::bearer($token);
        }

        if ($username && $password) {
            return Authenticate::basic($username, $password);
        }

        if (is_null($username) && is_null($password)) {
            return Authenticate::disabled();
        }

        throw new InvalidArgumentException('Invalid Neo4j authentication. Provide username/password or token.');
    }
}
