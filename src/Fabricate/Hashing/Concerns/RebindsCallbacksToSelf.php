<?php

declare(strict_types=1);

namespace Fabricate\Hashing\Concerns;

use Closure;
use ReflectionFunction;

trait RebindsCallbacksToSelf
{
    /**
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
