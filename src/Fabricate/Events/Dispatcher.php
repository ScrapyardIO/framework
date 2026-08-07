<?php

namespace Fabricate\Events;

use Closure;
use Fabricate\Contracts\Chassis\ServiceContainer;
use Fabricate\Contracts\Events\Dispatcher as DispatcherContract;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Concerns\Macroable;
use Fabricate\NutsAndBolts\Concerns\ReflectsClosures;
use Fabricate\NutsAndBolts\Str;

class Dispatcher implements DispatcherContract
{
    use Macroable;
    use ReflectsClosures;

    protected ServiceContainer $container;

    /**
     * @var array<string, array<int, callable|array|string|null>>
     */
    protected array $listeners = [];

    /**
     * @var array<string, array<int, callable|string>>
     */
    protected array $wildcards = [];

    /**
     * @var array<string, array<int, callable>>
     */
    protected array $wildcardsCache = [];

    /**
     * The events that have been deferred while `defer()` is buffering.
     *
     * @var array<int, array<int, mixed>>
     */
    protected array $deferredEvents = [];

    /**
     * Whether events are currently being deferred instead of dispatched.
     */
    protected bool $deferringEvents = false;

    /**
     * The specific events to defer, or null to defer all events.
     *
     * @var array<int, string>|null
     */
    protected ?array $eventsToDefer = null;

    // Queue / broadcast scaffolding intentionally kept visible for staged enablement:
    // - Queue-aware handlers (ShouldQueue, ShouldQueueAfterCommit)
    // - Listener job wrapping (CallQueuedListener)
    // - Broadcast dispatch (ShouldBroadcast + BroadcastFactory)
    // - Transaction deferral hooks (ShouldDispatchAfterCommit)
    //
    // These paths are currently disabled to keep core events stable while
    // queue/broadcast layers are still being brought online.

    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    public function listen(array|Closure|string $events, array|Closure|string|null $listener = null): void
    {
        if ($events instanceof Closure) {
            (new Collection($this->firstClosureParameterTypes($events)))
                ->each(fn (string $event) => $this->listen($event, $events));

            return;
        }

        foreach ((array) $events as $event) {
            if (str_contains($event, '*')) {
                $this->setupWildcardListen($event, $listener);
            } else {
                $this->listeners[$event][] = $listener;
            }
        }
    }

    protected function setupWildcardListen(string $event, callable|string|null $listener): void
    {
        if (is_null($listener)) {
            return;
        }

        $this->wildcards[$event][] = $listener;
        $this->wildcardsCache = [];
    }

    public function hasListeners(string $eventName): bool
    {
        return isset($this->listeners[$eventName])
            || isset($this->wildcards[$eventName])
            || $this->hasWildcardListeners($eventName);
    }

    public function hasWildcardListeners(string $eventName): bool
    {
        foreach ($this->wildcards as $key => $listeners) {
            if (Str::is($key, $eventName)) {
                return true;
            }
        }

        return false;
    }

    public function subscribe(object|string $subscriber): void
    {
        $subscriber = $this->resolveSubscriber($subscriber);

        $events = $subscriber->subscribe($this);

        if (! is_array($events)) {
            return;
        }

        foreach ($events as $event => $listeners) {
            foreach (Arr::wrap($listeners) as $listener) {
                if (is_string($listener) && method_exists($subscriber, $listener)) {
                    $this->listen($event, [get_class($subscriber), $listener]);
                    continue;
                }

                $this->listen($event, $listener);
            }
        }
    }

    protected function resolveSubscriber(object|string $subscriber): object
    {
        if (is_string($subscriber)) {
            return $this->container->make($subscriber);
        }

        return $subscriber;
    }

    public function until(object|string $event, mixed $payload = []): mixed
    {
        [$eventName, $parsedPayload] = $this->parseEventAndPayload($event, $payload);

        foreach ($this->getListeners($eventName) as $listener) {
            $response = $listener($eventName, $parsedPayload);

            if (! is_null($response)) {
                return $response;
            }
        }

        return null;
    }

    public function dispatch(object|string $event, mixed $payload = [], bool $halt = false): ?array
    {
        [$eventName, $parsedPayload] = $this->parseEventAndPayload($event, $payload);

        if ($this->shouldDeferEvent($eventName)) {
            $this->deferredEvents[] = func_get_args();

            return null;
        }

        // Future queue/broadcast hookpoint:
        // if ($this->shouldBroadcast($parsedPayload)) { ... }
        // if ($this->shouldQueueListener($listenerClass)) { ... }

        $responses = [];

        foreach ($this->getListeners($eventName) as $listener) {
            $response = $listener($eventName, $parsedPayload);

            if ($halt && ! is_null($response)) {
                return [$response];
            }

            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        return $halt ? null : $responses;
    }

    /**
     * @return array{string, array<int, mixed>}
     */
    protected function parseEventAndPayload(object|string $event, mixed $payload): array
    {
        if (is_object($event)) {
            return [get_class($event), [$event]];
        }

        return [$event, Arr::wrap($payload)];
    }

    /**
     * @return array<int, callable>
     */
    public function getListeners(string $eventName): array
    {
        $listeners = array_merge(
            $this->prepareListeners($eventName),
            $this->wildcardsCache[$eventName] ?? $this->getWildcardListeners($eventName)
        );

        if (! class_exists($eventName, false)) {
            return $listeners;
        }

        return $this->addInterfaceListeners($eventName, $listeners);
    }

    /**
     * @return array<int, callable>
     */
    protected function getWildcardListeners(string $eventName): array
    {
        $wildcards = [];

        foreach ($this->wildcards as $key => $listeners) {
            if (! Str::is($key, $eventName)) {
                continue;
            }

            foreach ($listeners as $listener) {
                $wildcards[] = $this->makeListener($listener, true);
            }
        }

        return $this->wildcardsCache[$eventName] = $wildcards;
    }

    /**
     * @param  array<int, callable>  $listeners
     * @return array<int, callable>
     */
    protected function addInterfaceListeners(string $eventName, array $listeners = []): array
    {
        foreach (class_implements($eventName) as $interface) {
            if (! isset($this->listeners[$interface])) {
                continue;
            }

            foreach ($this->prepareListeners($interface) as $listener) {
                $listeners[] = $listener;
            }
        }

        return $listeners;
    }

    /**
     * @return array<int, callable>
     */
    protected function prepareListeners(string $eventName): array
    {
        $listeners = [];

        foreach ($this->listeners[$eventName] ?? [] as $listener) {
            if (is_null($listener)) {
                continue;
            }

            $listeners[] = $this->makeListener($listener);
        }

        return $listeners;
    }

    public function makeListener(callable|array|string $listener, bool $wildcard = false): callable
    {
        if (is_string($listener) || (is_array($listener) && isset($listener[0]) && is_string($listener[0]))) {
            return $this->createClassListener($listener, $wildcard);
        }

        return function (string $event, array $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                return $listener($event, $payload);
            }

            return $listener(...array_values($payload));
        };
    }

    public function createClassListener(array|string $listener, bool $wildcard = false): callable
    {
        return function (string $event, array $payload) use ($listener, $wildcard) {
            $callable = $this->createClassCallable($listener);

            if ($wildcard) {
                return $callable($event, $payload);
            }

            return $callable(...array_values($payload));
        };
    }

    /**
     * @return callable
     */
    protected function createClassCallable(array|string $listener): callable
    {
        [$class, $method] = is_array($listener)
            ? $listener
            : Str::parseCallback($listener, 'handle');

        $instance = $this->container->make($class);

        if (! method_exists($instance, $method)) {
            $method = '__invoke';
        }

        return [$instance, $method];
    }

    public function push(string $event, array $payload = []): void
    {
        $this->listen($event.'_pushed', fn () => $this->dispatch($event, $payload));
    }

    public function flush(string $event): void
    {
        $this->dispatch($event.'_pushed');
    }

    public function forget(string $event): void
    {
        if (str_contains($event, '*')) {
            unset($this->wildcards[$event]);
        } else {
            unset($this->listeners[$event]);
        }

        foreach (array_keys($this->wildcardsCache) as $key) {
            if (Str::is($event, $key)) {
                unset($this->wildcardsCache[$key]);
            }
        }
    }

    public function forgetPushed(): void
    {
        foreach (array_keys($this->listeners) as $key) {
            if (str_ends_with($key, '_pushed')) {
                $this->forget($key);
            }
        }
    }

    /**
     * Execute the given callback while deferring events, then dispatch all deferred events.
     */
    public function defer(callable $callback, ?array $events = null): mixed
    {
        $wasDeferring = $this->deferringEvents;
        $previousDeferredEvents = $this->deferredEvents;
        $previousEventsToDefer = $this->eventsToDefer;

        $this->deferringEvents = true;
        $this->deferredEvents = [];
        $this->eventsToDefer = $events;

        try {
            $result = $callback();

            $this->deferringEvents = false;

            foreach ($this->deferredEvents as $args) {
                $this->dispatch(...$args);
            }

            return $result;
        } finally {
            $this->deferringEvents = $wasDeferring;
            $this->deferredEvents = $previousDeferredEvents;
            $this->eventsToDefer = $previousEventsToDefer;
        }
    }

    /**
     * Determine if the given event should be deferred.
     */
    protected function shouldDeferEvent(string $event): bool
    {
        return $this->deferringEvents && (is_null($this->eventsToDefer) || in_array($event, $this->eventsToDefer));
    }

    /**
     * Get the raw, unprepared listeners.
     *
     * @return array<string, array<int, callable|array|string|null>>
     */
    public function getRawListeners(): array
    {
        return $this->listeners;
    }
}
