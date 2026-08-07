<?php

namespace Fabricate\Graph\Database;

use Closure;
use Exception;
use Fabricate\Database\Connection;
use Fabricate\Graph\Database\Query\Grammars\Neo4jGrammar;
use Fabricate\Graph\Database\Query\Neo4jQueryBuilder;
use Fabricate\Graph\Database\Query\Processors\Neo4jProcessor;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Types\Node;
use Laudis\Neo4j\Types\Relationship;
use Throwable;

class Neo4jConnection extends Connection
{
    protected ClientInterface $neo4jClient;

    protected mixed $activeTransaction = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(ClientInterface $neo4jClient, string $database = '', string $tablePrefix = '', array $config = [])
    {
        $this->neo4jClient = $neo4jClient;

        parent::__construct(null, $database, $tablePrefix, $config);
    }

    public function getNeo4jClient(): ClientInterface
    {
        return $this->neo4jClient;
    }

    public function select($query, $bindings = [], $useReadPdo = true): array
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return [];
            }

            [$convertedQuery, $convertedBindings] = $this->getQueryGrammar()->convertParametersToNamed($query, $bindings);

            if (! is_null($this->activeTransaction)) {
                $result = $this->activeTransaction->run($convertedQuery, $convertedBindings);
            } else {
                $result = $this->neo4jClient->writeTransaction(function ($tx) use ($convertedQuery, $convertedBindings) {
                    return $tx->run($convertedQuery, $convertedBindings);
                });
            }

            $processedResults = [];
            foreach ($result as $record) {
                $row = [];

                foreach ($record->toArray() as $key => $value) {
                    if ($value instanceof Node) {
                        $row = array_merge($row, $value->getProperties()->toArray());
                    } elseif ($value instanceof Relationship) {
                        $row[$key] = $value->getProperties()->toArray();
                    } else {
                        $row[$key] = $value;
                    }
                }

                $processedResults[] = $row;
            }

            return $processedResults;
        });
    }

    public function selectOne($query, $bindings = [], $useReadPdo = true): mixed
    {
        $records = $this->select($query, $bindings, $useReadPdo);

        return array_shift($records);
    }

    public function statement($query, $bindings = []): bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            [$convertedQuery, $convertedBindings] = $this->getQueryGrammar()->convertParametersToNamed($query, $bindings);

            if (! is_null($this->activeTransaction)) {
                $this->activeTransaction->run($convertedQuery, $convertedBindings);
            } else {
                $this->neo4jClient->run($convertedQuery, $convertedBindings);
            }

            return true;
        });
    }

    public function affectingStatement($query, $bindings = []): int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            [$convertedQuery, $convertedBindings] = $this->getQueryGrammar()->convertParametersToNamed($query, $bindings);

            if (! is_null($this->activeTransaction)) {
                $result = $this->activeTransaction->run($convertedQuery, $convertedBindings);
            } else {
                $result = $this->neo4jClient->writeTransaction(function ($tx) use ($convertedQuery, $convertedBindings) {
                    return $tx->run($convertedQuery, $convertedBindings);
                });
            }

            $counters = $result->getSummary()->getCounters();

            return $counters->nodesCreated()
                + $counters->nodesDeleted()
                + $counters->relationshipsCreated()
                + $counters->relationshipsDeleted()
                + $counters->propertiesSet();
        });
    }

    public function insert($query, $bindings = []): bool
    {
        return $this->statement($query, $bindings);
    }

    public function update($query, $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function delete($query, $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function transaction(Closure $callback, $attempts = 1): mixed
    {
        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            $this->beginTransaction();

            try {
                $callbackResult = $callback($this);
                $this->commit();

                return $callbackResult;
            } catch (Throwable $e) {
                $this->rollBack();

                if ($currentAttempt >= $attempts) {
                    throw $e;
                }
            }
        }

        return null;
    }

    public function beginTransaction(): void
    {
        if ($this->transactions === 0 && is_null($this->activeTransaction)) {
            $this->activeTransaction = $this->neo4jClient->beginTransaction();
        }

        $this->transactions++;
        $this->fireConnectionEvent('beganTransaction');
    }

    public function commit(): void
    {
        if ($this->transactionLevel() === 1 && ! is_null($this->activeTransaction)) {
            $this->activeTransaction->commit();
            $this->activeTransaction = null;
        }

        $this->transactions = max(0, $this->transactions - 1);
        $this->fireConnectionEvent('committed');
    }

    public function rollBack($toLevel = null): void
    {
        $toLevel = is_null($toLevel) ? $this->transactions - 1 : $toLevel;

        if ($toLevel < 0 || $toLevel >= $this->transactions) {
            return;
        }

        if (! is_null($this->activeTransaction)) {
            try {
                $this->activeTransaction->rollback();
            } catch (Exception) {
                //
            } finally {
                $this->activeTransaction = null;
            }
        }

        $this->transactions = $toLevel;
        $this->fireConnectionEvent('rollingBack');
    }

    protected function getDefaultQueryGrammar()
    {
        return new Neo4jGrammar($this);
    }

    protected function getDefaultPostProcessor()
    {
        return new Neo4jProcessor;
    }

    public function query(): Neo4jQueryBuilder
    {
        return new Neo4jQueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    public function getDriverName(): string
    {
        return 'neo4j';
    }

    protected function run($query, $bindings, Closure $callback)
    {
        return $callback($query, $bindings);
    }
}
