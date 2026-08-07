<?php

namespace Fabricate\Redis\Connections;

use Closure;
use Fabricate\Contracts\Redis\Connection as ConnectionContract;
use Fabricate\NutsAndBolts\Collection;
use Predis\Command\Argument\ArrayableArgument;

/**
 * @mixin \Predis\Client
 */
class PredisConnection extends Connection implements ConnectionContract
{
    /**
     * The Predis client.
     *
     * @var \Predis\Client
     */
    protected $client;

    /**
     * Create a new Predis connection.
     *
     * @param  \Predis\Client  $client
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * Subscribe to a set of given channels for messages.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @param  string  $method
     * @return void
     */
    public function createSubscription($channels, Closure $callback, $method = 'subscribe')
    {
        $loop = $this->pubSubLoop();

        $loop->{$method}(...array_values((array) $channels));

        foreach ($loop as $message) {
            if ($message->kind === 'message' || $message->kind === 'pmessage') {
                $callback($message->payload, $message->channel);
            }
        }

        unset($loop);
    }

    /**
     * Scans all keys based on options.
     *
     * Predis rejects a null cursor (Redis 7.4+ returns ERR invalid cursor).
     * Coerce null → 0 so the PhpRedis-style scan loop works for both clients.
     *
     * @param  mixed  $cursor
     * @param  array  $options
     * @return array{0: string|int, 1: array}
     */
    public function scan($cursor, $options = [])
    {
        return $this->command('scan', [$cursor ?? 0, $options]);
    }

    /**
     * Scans the given sorted set for all values based on options.
     *
     * @param  string  $key
     * @param  mixed  $cursor
     * @param  array  $options
     * @return array{0: string|int, 1: array}
     */
    public function zscan($key, $cursor, $options = [])
    {
        return $this->command('zscan', [$key, $cursor ?? 0, $options]);
    }

    /**
     * Scans the given hash for all values based on options.
     *
     * @param  string  $key
     * @param  mixed  $cursor
     * @param  array  $options
     * @return array{0: string|int, 1: array}
     */
    public function hscan($key, $cursor, $options = [])
    {
        return $this->command('hscan', [$key, $cursor ?? 0, $options]);
    }

    /**
     * Scans the given set for all values based on options.
     *
     * @param  string  $key
     * @param  mixed  $cursor
     * @param  array  $options
     * @return array{0: string|int, 1: array}
     */
    public function sscan($key, $cursor, $options = [])
    {
        return $this->command('sscan', [$key, $cursor ?? 0, $options]);
    }

    /**
     * Parse the command's parameters for event dispatching.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function parseParametersForEvent(array $parameters)
    {
        return (new Collection($parameters))
            ->transform(function ($parameter) {
                return $parameter instanceof ArrayableArgument
                    ? $parameter->toArray()
                    : $parameter;
            })->all();
    }
}
