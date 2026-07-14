<?php

namespace BareMetal\Contracts\Events;

interface Dispatcher
{
    /**
     * Register an event listener with the dispatcher.
     */
    public function listen(callable|string|array $events, callable|string|array|null $listener = null): void;

    /**
     * Determine if a given event has listeners.
     */
    public function hasListeners($event_name): bool;

    /**
     * Register an event subscriber with the dispatcher.
     */
    public function subscribe(object|string $subscriber): void;

    /**
     * Dispatch an event until the first non-null response is returned.
     */
    public function until(string|object $event, mixed $payload = []): mixed;

    /**
     * Dispatch an event and call the listeners.
     */
    public function dispatch(string|object $event, mixed $payload = [], bool $halt = false): ?array;

    /**
     * Register an event and payload to be fired later.
     */
    public function push(string $event, array $payload = []): void;

    /**
     * Flush a set of pushed events.
     */
    public function flush(string $event): void;

    /**
     * Remove a set of listeners from the dispatcher.
     */
    public function forget(string $event): void;

    /**
     * Forget every queued listener.
     */
    public function forgetPushed(): void;
}
