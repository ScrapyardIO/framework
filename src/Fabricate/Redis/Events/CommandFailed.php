<?php

namespace Fabricate\Redis\Events;

use Fabricate\Redis\Connections\Connection;
use Throwable;

class CommandFailed
{
    /**
     * The Redis command that failed.
     *
     * @var string
     */
    public string $command;

    /**
     * The array of command parameters.
     *
     * @var array
     */
    public array $parameters;

    /**
     * The exception that was thrown.
     *
     * @var \Throwable
     */
    public Throwable $exception;

    /**
     * The Redis connection instance.
     *
     * @var Connection
     */
    public Connection $connection;

    /**
     * The Redis connection name.
     *
     * @var string
     */
    public ?string $connectionName;

    /**
     * Create a new event instance.
     *
     * @param string $command
     * @param array $parameters
     * @param  \Throwable  $exception
     * @param Connection $connection
     */
    public function __construct(string $command, array $parameters, Throwable $exception, Connection $connection)
    {
        $this->command = $command;
        $this->parameters = $parameters;
        $this->exception = $exception;
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
