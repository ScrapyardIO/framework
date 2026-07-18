<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

/**
 * @method static \Psr\Log\LoggerInterface build(array $config)
 * @method static \Psr\Log\LoggerInterface stack(array $channels, string|null $channel = null)
 * @method static \Psr\Log\LoggerInterface channel(\UnitEnum|string|null $channel = null)
 * @method static \Psr\Log\LoggerInterface driver(\UnitEnum|string|null $driver = null)
 * @method static \Fabricate\Log\LogManager shareContext(array $context)
 * @method static array sharedContext()
 * @method static \Fabricate\Log\LogManager withoutContext(string[]|null $keys = null)
 * @method static \Fabricate\Log\LogManager flushSharedContext()
 * @method static string|null getDefaultDriver()
 * @method static void setDefaultDriver(\UnitEnum|string $name)
 * @method static \Fabricate\Log\LogManager extend(string $driver, \Closure $callback)
 * @method static void forgetChannel(string|null $driver = null)
 * @method static array getChannels()
 * @method static void emergency(string|\Stringable $message, array $context = [])
 * @method static void alert(string|\Stringable $message, array $context = [])
 * @method static void critical(string|\Stringable $message, array $context = [])
 * @method static void error(string|\Stringable $message, array $context = [])
 * @method static void warning(string|\Stringable $message, array $context = [])
 * @method static void notice(string|\Stringable $message, array $context = [])
 * @method static void info(string|\Stringable $message, array $context = [])
 * @method static void debug(string|\Stringable $message, array $context = [])
 * @method static void log(mixed $level, string|\Stringable $message, array $context = [])
 * @method static \Fabricate\Log\LogManager setApplication(\Fabricate\Contracts\Core\Machine $app)
 * @method static void write(string $level, \Fabricate\Contracts\Support\Arrayable|\Fabricate\Contracts\Support\Jsonable|\Fabricate\NutsAndBolts\Stringable|array|string $message, array $context = [])
 * @method static \Fabricate\Log\Logger withContext(array $context = [])
 * @method static void listen(\Closure $callback)
 * @method static \Psr\Log\LoggerInterface getLogger()
 * @method static \Fabricate\Contracts\Events\Dispatcher|null getEventDispatcher()
 * @method static void setEventDispatcher(\Fabricate\Contracts\Events\Dispatcher $dispatcher)
 * @method static \Fabricate\Log\Logger|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Fabricate\Log\Logger|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 *
 * @see \Fabricate\Log\LogManager
 */
class Log extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'log';
    }
}
