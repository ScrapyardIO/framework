<?php

namespace Fabricate\Contracts\Events;

use Closure;

interface Dispatcher
{
    /**
     * Register an event listener with the dispatcher.
     *
     * @param array|Closure|string $events
     * @param array|Closure|string|null $listener
     * @return void
     */
    public function listen(array|Closure|string $events, array|Closure|string|null $listener = null): void;

    /**
     * Determine if a given event has listeners.
     *
     * @param string $eventName
     * @return bool
     */
    public function hasListeners(string $eventName): bool;

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param object|string $subscriber
     * @return void
     */
    public function subscribe(object|string $subscriber): void;

    /**
     * Dispatch an event until the first non-null response is returned.
     *
     * @param object|string $event
     * @param mixed|array $payload
     * @return mixed
     */
    public function until(object|string $event, mixed $payload = []): mixed;

    /**
     * Dispatch an event and call the listeners.
     *
     * @param object|string $event
     * @param mixed|array $payload
     * @param bool $halt
     * @return array|null
     */
    public function dispatch(object|string $event, mixed $payload = [], bool $halt = false): ?array;

    /**
     * Register an event and payload to be fired later.
     *
     * @param string $event
     * @param array $payload
     * @return void
     */
    public function push(string $event, array $payload = []): void;

    /**
     * Flush a set of pushed events.
     *
     * @param string $event
     * @return void
     */
    public function flush(string $event): void;

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param string $event
     * @return void
     */
    public function forget(string $event): void;

    /**
     * Forget all of the queued listeners.
     *
     * @return void
     */
    public function forgetPushed(): void;
}
