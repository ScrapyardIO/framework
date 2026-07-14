<?php

namespace BareMetal\Events;

use BareMetal\Contracts\Chassis\Chassis;
use BareMetal\Contracts\Events\Dispatcher as DispatcherContract;
use Closure;
use ScrapyardIO\NutsAndBolts\Arr;
use ScrapyardIO\NutsAndBolts\Concerns\Macroable;
use ScrapyardIO\NutsAndBolts\Concerns\ReflectsClosures;
use ScrapyardIO\NutsAndBolts\Str;
use ScrpayardIO\NutsAndBolts\Collection;

class Dispatcher implements DispatcherContract
{
    use Macroable, ReflectsClosures;

    /**
     * The IoC container instance.
     */
    protected Chassis $container;

    /**
     * The registered event listeners.
     *
     * @var array<string, array<int, mixed>>
     */
    protected array $listeners = [];

    /**
     * The wildcard listeners.
     *
     * @var array<string, array<int, mixed>>
     */
    protected array $wildcards = [];

    /**
     * The cached wildcard listeners.
     *
     * @var array<string, array<int, Closure>>
     */
    protected array $wildcards_cache = [];

    /**
     * Create a new event dispatcher instance.
     */
    public function __construct(?Chassis $container = null)
    {
        if (is_null($container)) {
            throw new \InvalidArgumentException('Event Dispatcher requires a container instance.');
        }

        $this->container = $container;
    }

    /**
     * Register an event listener with the dispatcher.
     */
    public function listen(callable|string|array $events, callable|string|array|null $listener = null): void
    {
        if ($events instanceof Closure) {
            (new Collection($this->firstClosureParameterTypes($events)))
                ->each(function ($event) use ($events) {
                    $this->listen($event, $events);
                });

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

    /**
     * Setup a wildcard listener callback.
     */
    protected function setupWildcardListen(string $event, mixed $listener): void
    {
        $this->wildcards[$event][] = $listener;

        $this->wildcards_cache = [];
    }

    /**
     * Determine if a given event has listeners.
     */
    public function hasListeners($event_name): bool
    {
        return isset($this->listeners[$event_name]) ||
               isset($this->wildcards[$event_name]) ||
               $this->hasWildcardListeners($event_name);
    }

    /**
     * Determine if the given event has any wildcard listeners.
     */
    protected function hasWildcardListeners(string $event_name): bool
    {
        foreach ($this->wildcards as $key => $listeners) {
            if (Str::is($key, $event_name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Register an event and payload to be fired later.
     */
    public function push(string $event, array $payload = []): void
    {
        $this->listen($event.'_pushed', function () use ($event, $payload) {
            $this->dispatch($event, $payload);
        });
    }

    /**
     * Flush a set of pushed events.
     */
    public function flush(string $event): void
    {
        $this->dispatch($event.'_pushed');
    }

    /**
     * Register an event subscriber with the dispatcher.
     */
    public function subscribe(object|string $subscriber): void
    {
        $subscriber = $this->resolveSubscriber($subscriber);

        $events = $subscriber->subscribe($this);

        if (is_array($events)) {
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
    }

    /**
     * Resolve the subscriber instance.
     */
    protected function resolveSubscriber(object|string $subscriber): object
    {
        if (is_string($subscriber)) {
            return $this->container->make($subscriber);
        }

        return $subscriber;
    }

    /**
     * Dispatch an event until the first non-null response is returned.
     */
    public function until(string|object $event, mixed $payload = []): mixed
    {
        return $this->dispatch($event, $payload, true);
    }

    /**
     * Dispatch an event and call the listeners.
     */
    public function dispatch(string|object $event, mixed $payload = [], bool $halt = false): ?array
    {
        [$parsed_event, $parsed_payload] = $this->parseEventAndPayload($event, $payload);

        return $this->invokeListeners($parsed_event, $parsed_payload, $halt);
    }

    /**
     * Call the listeners for the given event.
     */
    protected function invokeListeners(string $event, array $payload, bool $halt = false): mixed
    {
        $responses = [];

        foreach ($this->getListeners($event) as $listener) {
            $response = $listener($event, $payload);

            if ($halt && ! is_null($response)) {
                return $response;
            }

            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        return $halt ? null : $responses;
    }

    /**
     * Parse the given event and payload and prepare them for dispatching.
     *
     * @return array{0: string, 1: array}
     */
    protected function parseEventAndPayload(mixed $event, mixed $payload): array
    {
        if (is_object($event)) {
            [$payload, $event] = [[$event], get_class($event)];
        }

        return [$event, Arr::wrap($payload)];
    }

    /**
     * Get all of the listeners for a given event name.
     *
     * @return array<int, Closure>
     */
    public function getListeners(string $event_name): array
    {
        $listeners = array_merge(
            $this->prepareListeners($event_name),
            $this->wildcards_cache[$event_name] ?? $this->getWildcardListeners($event_name)
        );

        return class_exists($event_name, false)
            ? $this->addInterfaceListeners($event_name, $listeners)
            : $listeners;
    }

    /**
     * Get the wildcard listeners for the event.
     *
     * @return array<int, Closure>
     */
    protected function getWildcardListeners(string $event_name): array
    {
        $wildcards = [];

        foreach ($this->wildcards as $key => $listeners) {
            if (Str::is($key, $event_name)) {
                foreach ($listeners as $listener) {
                    $wildcards[] = $this->makeListener($listener, true);
                }
            }
        }

        return $this->wildcards_cache[$event_name] = $wildcards;
    }

    /**
     * Add the listeners for the event's interfaces to the given array.
     */
    protected function addInterfaceListeners(string $event_name, array $listeners = []): array
    {
        foreach (class_implements($event_name) as $interface) {
            if (isset($this->listeners[$interface])) {
                foreach ($this->prepareListeners($interface) as $names) {
                    $listeners = array_merge($listeners, (array) $names);
                }
            }
        }

        return $listeners;
    }

    /**
     * Prepare the listeners for a given event.
     *
     * @return list<Closure>
     */
    protected function prepareListeners(string $event_name): array
    {
        $listeners = [];

        foreach ($this->listeners[$event_name] ?? [] as $listener) {
            $listeners[] = $this->makeListener($listener);
        }

        return $listeners;
    }

    /**
     * Register an event listener with the dispatcher.
     */
    public function makeListener(mixed $listener, bool $wildcard = false): Closure
    {
        if (is_string($listener)) {
            return $this->createClassListener($listener, $wildcard);
        }

        if (is_array($listener) && isset($listener[0]) && is_string($listener[0])) {
            return $this->createClassListener($listener, $wildcard);
        }

        return function ($event, $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                return $listener($event, $payload);
            }

            return $listener(...array_values($payload));
        };
    }

    /**
     * Create a class based listener using the IoC container.
     */
    public function createClassListener(array|string $listener, bool $wildcard = false): Closure
    {
        return function ($event, $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                return call_user_func($this->createClassCallable($listener), $event, $payload);
            }

            $callable = $this->createClassCallable($listener);

            return $callable(...array_values($payload));
        };
    }

    /**
     * Create the class based event callable.
     */
    protected function createClassCallable(array|string $listener): callable
    {
        [$class, $method] = is_array($listener)
            ? $listener
            : $this->parseClassCallable($listener);

        if (! method_exists($class, $method)) {
            $method = '__invoke';
        }

        $listener = $this->container->make($class);

        return [$listener, $method];
    }

    /**
     * Parse the class listener into class and method.
     *
     * @return array{0: class-string, 1: string}
     */
    protected function parseClassCallable(string $listener): array
    {
        return Str::parseCallback($listener, 'handle');
    }

    /**
     * Remove a set of listeners from the dispatcher.
     */
    public function forget(string $event): void
    {
        if (str_contains($event, '*')) {
            unset($this->wildcards[$event]);
        } else {
            unset($this->listeners[$event]);
        }

        foreach ($this->wildcards_cache as $key => $listeners) {
            if (Str::is($event, $key)) {
                unset($this->wildcards_cache[$key]);
            }
        }
    }

    /**
     * Forget every queued listener.
     */
    public function forgetPushed(): void
    {
        foreach ($this->listeners as $key => $value) {
            if (str_ends_with($key, '_pushed')) {
                $this->forget($key);
            }
        }
    }

    /**
     * Get all of the listeners from the dispatcher for a raw dump.
     */
    public function getRawListeners(): array
    {
        return $this->listeners;
    }
}
