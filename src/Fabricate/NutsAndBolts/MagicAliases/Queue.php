<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

/**
 * @method static void before(mixed $callback)
 * @method static void after(mixed $callback)
 * @method static void exceptionOccurred(mixed $callback)
 * @method static void looping(mixed $callback)
 * @method static void failing(mixed $callback)
 * @method static void starting(mixed $callback)
 * @method static void stopping(mixed $callback)
 * @method static void route(array|string $class, \UnitEnum|string|null $queue = null, \UnitEnum|string|null $connection = null)
 * @method static bool connected(\UnitEnum|string|null $name = null)
 * @method static \Fabricate\Contracts\Queue\Queue connection(\UnitEnum|string|null $name = null)
 * @method static void extend(string $driver, \Closure $resolver)
 * @method static void addConnector(string $driver, \Closure $resolver)
 * @method static string getDefaultDriver()
 * @method static void setDefaultDriver(\UnitEnum|string $name)
 * @method static string getName(string|null $connection = null)
 * @method static \Fabricate\Contracts\Core\Machine getApplication()
 * @method static \Fabricate\Queue\QueueManager setApplication(\Fabricate\Contracts\Core\Machine $app)
 * @method static string|null resolveConnectionFromQueueRoute(object $queueable)
 * @method static string|null resolveQueueFromQueueRoute(object $queueable)
 * @method static int size(string|null $queue = null)
 * @method static mixed push(string|object $job, mixed $data = '', string|null $queue = null)
 * @method static mixed pushOn(string $queue, string|object $job, mixed $data = '')
 * @method static mixed pushRaw(string $payload, string|null $queue = null, array $options = [])
 * @method static mixed later(\DateTimeInterface|\DateInterval|int $delay, string|object $job, mixed $data = '', string|null $queue = null)
 * @method static mixed laterOn(string $queue, \DateTimeInterface|\DateInterval|int $delay, string|object $job, mixed $data = '')
 * @method static mixed bulk(array $jobs, mixed $data = '', string|null $queue = null)
 * @method static \Fabricate\Contracts\Queue\Job|null pop(string|null $queue = null)
 * @method static string getConnectionName()
 * @method static \Fabricate\Contracts\Queue\Queue setConnectionName(string $name)
 *
 * @see \Fabricate\Queue\QueueManager
 * @see \Fabricate\Queue\SyncQueue
 */
class Queue extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'queue';
    }
}
