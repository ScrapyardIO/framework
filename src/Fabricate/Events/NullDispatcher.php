<?php

namespace Fabricate\Events;

use Closure;
use Fabricate\Contracts\Events\Dispatcher as DispatcherContract;
use Fabricate\NutsAndBolts\Concerns\ForwardsCalls;

class NullDispatcher implements DispatcherContract
{
    use ForwardsCalls;

    /**
     * The underlying event dispatcher instance.
     */
    protected DispatcherContract $dispatcher;

    /**
     * Create a new event dispatcher instance that does not fire.
     */
    public function __construct(DispatcherContract $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Don't fire an event.
     */
    public function dispatch(object|string $event, mixed $payload = [], bool $halt = false): ?array
    {
        return null;
    }

    /**
     * Don't register an event and payload to be fired later.
     */
    public function push(string $event, array $payload = []): void
    {
        //
    }

    /**
     * Don't dispatch an event.
     */
    public function until(object|string $event, mixed $payload = []): mixed
    {
        return null;
    }

    /**
     * Register an event listener with the dispatcher.
     */
    public function listen(array|Closure|string $events, array|Closure|string|null $listener = null): void
    {
        $this->dispatcher->listen($events, $listener);
    }

    /**
     * Determine if a given event has listeners.
     */
    public function hasListeners(string $eventName): bool
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * Determine if the given event has wildcard listeners.
     */
    public function hasWildcardListeners(string $eventName): bool
    {
        return $this->dispatcher->hasWildcardListeners($eventName);
    }

    /**
     * Register an event subscriber with the dispatcher.
     */
    public function subscribe(object|string $subscriber): void
    {
        $this->dispatcher->subscribe($subscriber);
    }

    /**
     * Flush a set of pushed events.
     */
    public function flush(string $event): void
    {
        $this->dispatcher->flush($event);
    }

    /**
     * Remove a set of listeners from the dispatcher.
     */
    public function forget(string $event): void
    {
        $this->dispatcher->forget($event);
    }

    /**
     * Forget all of the queued listeners.
     */
    public function forgetPushed(): void
    {
        $this->dispatcher->forgetPushed();
    }

    /**
     * Don't defer events; run the callback immediately.
     */
    public function defer(callable $callback, ?array $events = null): mixed
    {
        return $callback();
    }

    /**
     * Get the raw, unprepared listeners.
     */
    public function getRawListeners(): array
    {
        return $this->dispatcher->getRawListeners();
    }

    /**
     * Dynamically pass method calls to the underlying dispatcher.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardDecoratedCallTo($this->dispatcher, $method, $parameters);
    }
}
