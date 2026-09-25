<?php

namespace GeneralPurposeIO\Contracts\Core;

use RuntimeException;
use Throwable;
use Voyager\Contracts\IOPools\RemoteException;

/** Root of every exception this framework throws. Catch one type without naming a protocol. */
class GPIOLevelException extends RuntimeException
{
    public static function invalidProperty(string $name, string $class): static
    {
        return new static("Invalid property [{$name}] on [{$class}]");
    }

    /**
     * A pool worker's exception crosses the pipe as a RemoteException naming its class. When that class is one
     * of ours, hand back a fresh one with the same message, so a promise rejects with what the blocking call throws.
     * The RemoteException, worker trace and all, rides along as its previous.
     */
    public static function localize(Throwable $e): Throwable
    {
        if (! $e instanceof RemoteException || ! is_a($e->remote_class, self::class, true)) {
            return $e;
        }

        $prefix = "[{$e->remote_class}] ";
        $message = str_starts_with($e->getMessage(), $prefix) ? substr($e->getMessage(), strlen($prefix)) : $e->getMessage();

        return new ($e->remote_class)($message, 0, $e);        // the worker's trace stays reachable as getPrevious()
    }
}
