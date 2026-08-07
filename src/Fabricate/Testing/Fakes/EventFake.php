<?php

namespace Fabricate\Testing\Fakes;

use Closure;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Concerns\ForwardsCalls;
use Fabricate\NutsAndBolts\Concerns\ReflectsClosures;
use Fabricate\NutsAndBolts\Str;
use PHPUnit\Framework\Assert as PHPUnit;
use ReflectionFunction;

class EventFake implements Dispatcher, Fake
{
    use ForwardsCalls;
    use ReflectsClosures;

    /**
     * The original event dispatcher.
     */
    public Dispatcher $dispatcher;

    /**
     * The event types that should be intercepted instead of dispatched.
     *
     * @var array
     */
    protected array $eventsToFake = [];

    /**
     * The event types that should be dispatched instead of intercepted.
     *
     * @var array
     */
    protected array $eventsToDispatch = [];

    /**
     * All of the events that have been intercepted keyed by type.
     *
     * @var array
     */
    protected array $events = [];

    /**
     * Create a new event fake instance.
     *
     * @param  array|string  $eventsToFake
     */
    public function __construct(Dispatcher $dispatcher, array|string $eventsToFake = [])
    {
        $this->dispatcher = $dispatcher;

        $this->eventsToFake = Arr::wrap($eventsToFake);
    }

    /**
     * Specify the events that should be dispatched instead of faked.
     *
     * @param  array|string  $eventsToDispatch
     * @return $this
     */
    public function except(array|string $eventsToDispatch): static
    {
        $this->eventsToDispatch = array_merge(
            $this->eventsToDispatch,
            Arr::wrap($eventsToDispatch)
        );

        return $this;
    }

    /**
     * Assert if an event has a listener attached to it.
     *
     * @param  string|array  $expectedListener
     */
    public function assertListening(string $expectedEvent, string|array $expectedListener): void
    {
        foreach ($this->dispatcher->getListeners($expectedEvent) as $listenerClosure) {
            $actualListener = (new ReflectionFunction($listenerClosure))
                ->getStaticVariables()['listener'];

            $normalizedListener = $expectedListener;

            if (is_string($actualListener) && Str::contains($actualListener, '@')) {
                $actualListener = Str::parseCallback($actualListener);

                if (is_string($expectedListener)) {
                    if (Str::contains($expectedListener, '@')) {
                        $normalizedListener = Str::parseCallback($expectedListener);
                    } else {
                        $normalizedListener = [
                            $expectedListener,
                            method_exists($expectedListener, 'handle') ? 'handle' : '__invoke',
                        ];
                    }
                }
            }

            if ($actualListener === $normalizedListener ||
                ($actualListener instanceof Closure &&
                $normalizedListener === Closure::class)) {
                PHPUnit::assertTrue(true);

                return;
            }
        }

        PHPUnit::assertTrue(
            false,
            sprintf(
                'Event [%s] does not have the [%s] listener attached to it',
                $expectedEvent,
                print_r($expectedListener, true)
            )
        );
    }

    /**
     * Assert if an event was dispatched based on a truth-test callback.
     *
     * @param  string|Closure  $event
     * @param  callable|int|null  $callback
     */
    public function assertDispatched(string|Closure $event, callable|int|null $callback = null): void
    {
        if ($event instanceof Closure) {
            [$event, $callback] = [$this->firstClosureParameterType($event), $event];
        }

        if (is_int($callback)) {
            $this->assertDispatchedTimes($event, $callback);

            return;
        }

        PHPUnit::assertTrue(
            $this->dispatched($event, $callback)->count() > 0,
            "The expected [{$event}] event was not dispatched."
        );
    }

    /**
     * Assert if an event was dispatched exactly once.
     */
    public function assertDispatchedOnce(string $event): void
    {
        $this->assertDispatchedTimes($event, 1);
    }

    /**
     * Assert if an event was dispatched a number of times.
     */
    public function assertDispatchedTimes(string $event, int $times = 1): void
    {
        $count = $this->dispatched($event)->count();

        PHPUnit::assertSame(
            $times, $count,
            sprintf(
                "The expected [{$event}] event was dispatched {$count} %s instead of {$times} %s.",
                Str::plural('time', $count),
                Str::plural('time', $times)
            )
        );
    }

    /**
     * Determine if an event was dispatched based on a truth-test callback.
     *
     * @param  string|Closure  $event
     * @param  callable|null  $callback
     */
    public function assertNotDispatched(string|Closure $event, callable|null $callback = null): void
    {
        if ($event instanceof Closure) {
            [$event, $callback] = [$this->firstClosureParameterType($event), $event];
        }

        PHPUnit::assertCount(
            0, $this->dispatched($event, $callback),
            "The unexpected [{$event}] event was dispatched."
        );
    }

    /**
     * Assert that no events were dispatched.
     */
    public function assertNothingDispatched(): void
    {
        $count = count(Arr::flatten($this->events));

        $eventNames = (new Collection($this->events))
            ->map(fn ($events, $eventName) => sprintf(
                '%s dispatched %s %s',
                $eventName,
                count($events),
                Str::plural('time', count($events)),
            ))
            ->join("\n- ");

        PHPUnit::assertSame(
            0, $count,
            "{$count} unexpected events were dispatched:\n\n- $eventNames\n"
        );
    }

    /**
     * Get all of the events matching a truth-test callback.
     *
     * @param  callable|null  $callback
     */
    public function dispatched(string $event, callable|null $callback = null): Collection
    {
        if (! $this->hasDispatched($event)) {
            return new Collection;
        }

        $callback = $callback ?: fn () => true;

        return (new Collection($this->events[$event]))->filter(
            fn ($arguments) => $callback(...$arguments)
        );
    }

    /**
     * Determine if the given event has been dispatched.
     */
    public function hasDispatched(string $event): bool
    {
        return isset($this->events[$event]) && ! empty($this->events[$event]);
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
     * Register an event and payload to be dispatched later.
     */
    public function push(string $event, array $payload = []): void
    {
        //
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
        //
    }

    /**
     * Fire an event and call the listeners.
     */
    public function dispatch(object|string $event, mixed $payload = [], bool $halt = false): ?array
    {
        $name = is_object($event) ? get_class($event) : (string) $event;

        if ($this->shouldFakeEvent($name, $payload)) {
            $this->fakeEvent($name, func_get_args());
        } else {
            return $this->dispatcher->dispatch($event, $payload, $halt);
        }

        return null;
    }

    /**
     * Determine if an event should be faked or actually dispatched.
     */
    protected function shouldFakeEvent(string $eventName, mixed $payload): bool
    {
        if ($this->shouldDispatchEvent($eventName, $payload)) {
            return false;
        }

        if (empty($this->eventsToFake)) {
            return true;
        }

        return (new Collection($this->eventsToFake))
            ->filter(function ($event) use ($eventName, $payload) {
                return $event instanceof Closure
                    ? $event($eventName, $payload)
                    : $event === $eventName;
            })
            ->isNotEmpty();
    }

    /**
     * Record the event onto the fake events array immediately.
     */
    protected function fakeEvent(string $name, array $arguments): void
    {
        $this->events[$name][] = $arguments;
    }

    /**
     * Determine whether an event should be dispatched or not.
     */
    protected function shouldDispatchEvent(string $eventName, mixed $payload): bool
    {
        if (empty($this->eventsToDispatch)) {
            return false;
        }

        return (new Collection($this->eventsToDispatch))
            ->filter(function ($event) use ($eventName, $payload) {
                return $event instanceof Closure
                    ? $event($eventName, $payload)
                    : $event === $eventName;
            })
            ->isNotEmpty();
    }

    /**
     * Remove a set of listeners from the dispatcher.
     */
    public function forget(string $event): void
    {
        //
    }

    /**
     * Forget all of the queued listeners.
     */
    public function forgetPushed(): void
    {
        //
    }

    /**
     * Dispatch an event and call the listeners.
     */
    public function until(object|string $event, mixed $payload = []): mixed
    {
        return $this->dispatch($event, $payload, true);
    }

    /**
     * Execute the given callback while deferring events, then dispatch all deferred events.
     *
     * Fakes have nothing to buffer, so the callback simply runs immediately.
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
     * Get the events that have been dispatched.
     */
    public function dispatchedEvents(): array
    {
        return $this->events;
    }

    /**
     * Handle dynamic method calls to the dispatcher.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->dispatcher, $method, $parameters);
    }
}
