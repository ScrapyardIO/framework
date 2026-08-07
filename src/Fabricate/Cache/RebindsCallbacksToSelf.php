<?php

declare(strict_types=1);

namespace Fabricate\Cache;

use Closure;
use ReflectionFunction;

trait RebindsCallbacksToSelf
{
    /**
     * Binds the provided callback to the class instance.
     *
     * @throws \ReflectionException
     */
    protected function bindCallbackToSelf(Closure $callback): ?Closure
    {
        $reflector = new ReflectionFunction($callback);

        if ($reflector->isAnonymous()) {
            if ($reflector->isStatic()) {
                $callback = $callback->bindTo(null, static::class);
            } else {
                $callback = $callback->bindTo($this, static::class);
            }
        }

        return $callback;
    }
}
