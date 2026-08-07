<?php

namespace Fabricate\Core\Events;

trait Dispatchable
{
    /**
     * Dispatch the event with the given arguments.
     */
    public static function dispatch(mixed ...$arguments): mixed
    {
        return event(new static(...$arguments));
    }

    /**
     * Dispatch the event with the given arguments if the given truth test passes.
     */
    public static function dispatchIf(bool $boolean, mixed ...$arguments): mixed
    {
        if ($boolean) {
            return event(new static(...$arguments));
        }

        return null;
    }

    /**
     * Dispatch the event with the given arguments unless the given truth test passes.
     */
    public static function dispatchUnless(bool $boolean, mixed ...$arguments): mixed
    {
        if (! $boolean) {
            return event(new static(...$arguments));
        }

        return null;
    }
}
