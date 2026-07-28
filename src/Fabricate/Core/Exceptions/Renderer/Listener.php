<?php

namespace Fabricate\Core\Exceptions\Renderer;

use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\Database\Events\QueryExecuted;
use Fabricate\Queue\Events\JobProcessed;
use Fabricate\Queue\Events\JobProcessing;

class Listener
{
    /**
     * The queries that have been executed.
     *
     * @var array<int, array{connectionName: string, time: float, sql: string, bindings: array}>
     */
    protected $queries = [];

    /**
     * Register the appropriate listeners on the given event dispatcher.
     *
     * @param Dispatcher $events
     * @return void
     */
    public function registerListeners(Dispatcher $events): void
    {
        $events->listen(QueryExecuted::class, $this->onQueryExecuted(...));

        $events->listen([JobProcessing::class, JobProcessed::class], function () {
            $this->queries = [];
        });
    }

    /**
     * Returns the queries that have been executed.
     *
     * @return array<int, array{connectionName: string, time: float, sql: string, bindings: array}>
     */
    public function queries(): array
    {
        return $this->queries;
    }

    /**
     * Listens for the query executed event.
     *
     * @param QueryExecuted $event
     * @return void
     */
    public function onQueryExecuted(QueryExecuted $event)
    {
        if (count($this->queries) >= 100) {
            return;
        }

        $sql = strlen($event->sql) <= 2000
            ? $event->sql
            : mb_strcut($event->sql, 0, 2000);

        $bindings = $event->connection->prepareBindings($event->bindings);

        $bindingCount = substr_count($sql, '?');

        $this->queries[] = [
            'connectionName' => $event->connectionName,
            'time' => $event->time,
            'sql' => $sql,
            'bindings' => count($bindings) <= $bindingCount
                ? $bindings
                : array_slice($bindings, 0, $bindingCount),
        ];
    }
}