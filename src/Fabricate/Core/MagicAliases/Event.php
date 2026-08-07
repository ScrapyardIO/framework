<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\MagicAliases\MagicAlias;
use Fabricate\Testing\Fakes\EventFake;

/**
 * @method static void listen(\Closure|callable|array|string $events, \Closure|callable|array|string|null $listener = null)
 * @method static bool hasListeners(string $eventName)
 * @method static bool hasWildcardListeners(string $eventName)
 * @method static void push(string $event, object|array $payload = [])
 * @method static void flush(string $event)
 * @method static void subscribe(object|string $subscriber)
 * @method static mixed until(string|object $event, mixed $payload = [])
 * @method static array|null dispatch(string|object $event, mixed $payload = [], bool $halt = false)
 * @method static array getListeners(string $eventName)
 * @method static \Closure makeListener(\Closure|string|array $listener, bool $wildcard = false)
 * @method static \Closure createClassListener(string|array $listener, bool $wildcard = false)
 * @method static void forget(string $event)
 * @method static void forgetPushed()
 * @method static mixed defer(callable $callback, string[]|null $events = null)
 * @method static array getRawListeners()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static \Fabricate\Testing\Fakes\EventFake except(array|string $eventsToDispatch)
 * @method static void assertListening(string $expectedEvent, string|array $expectedListener)
 * @method static void assertDispatched(string|\Closure $event, callable|int|null $callback = null)
 * @method static void assertDispatchedOnce(string $event)
 * @method static void assertDispatchedTimes(string $event, int $times = 1)
 * @method static void assertNotDispatched(string|\Closure $event, callable|null $callback = null)
 * @method static void assertNothingDispatched()
 * @method static \Fabricate\NutsAndBolts\Collection dispatched(string $event, callable|null $callback = null)
 * @method static bool hasDispatched(string $event)
 * @method static array dispatchedEvents()
 *
 * @see \Fabricate\Events\Dispatcher
 * @see \Fabricate\Testing\Fakes\EventFake
 */
class Event extends MagicAlias
{
    /**
     * Replace the bound instance with a fake.
     *
     * @param  array|string  $eventsToFake
     * @return EventFake
     */
    public static function fake(array|string $eventsToFake = []): EventFake
    {
        $actualDispatcher = static::isFake()
            ? static::getMagicAliasRoot()->dispatcher
            : static::getMagicAliasRoot();

        return tap(new EventFake($actualDispatcher, $eventsToFake), function (EventFake $fake) {
            static::swap($fake);

            // Model::setEventDispatcher($fake);
            if (app()->bound('cache')) {
                Cache::refreshEventDispatcher();
            }
        });
    }

    /**
     * Replace the bound instance with a fake that fakes all events except the given events.
     *
     * @param  array|string  $eventsToAllow
     * @return EventFake
     */
    public static function fakeExcept(array|string $eventsToAllow): EventFake
    {
        return static::fake([
            function (string $eventName) use ($eventsToAllow) {
                return ! in_array($eventName, (array) $eventsToAllow);
            },
        ]);
    }

    /**
     * Replace the bound instance with a fake during the given callable's execution.
     *
     * @return mixed
     */
    public static function fakeFor(callable $callable, array $eventsToFake = []): mixed
    {
        $originalDispatcher = static::getMagicAliasRoot();

        static::fake($eventsToFake);

        try {
            return $callable();
        } finally {
            static::swap($originalDispatcher);

            // Model::setEventDispatcher($originalDispatcher);
            if (app()->bound('cache')) {
                Cache::refreshEventDispatcher();
            }
        }
    }

    /**
     * Replace the bound instance with a fake during the given callable's execution,
     * faking all events except the given events.
     *
     * @return mixed
     */
    public static function fakeExceptFor(callable $callable, array $eventsToAllow = []): mixed
    {
        $originalDispatcher = static::getMagicAliasRoot();

        static::fakeExcept($eventsToAllow);

        try {
            return $callable();
        } finally {
            static::swap($originalDispatcher);

            // Model::setEventDispatcher($originalDispatcher);
            if (app()->bound('cache')) {
                Cache::refreshEventDispatcher();
            }
        }
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'events';
    }
}
