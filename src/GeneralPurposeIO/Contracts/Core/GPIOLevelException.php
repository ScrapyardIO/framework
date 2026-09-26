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

    public static function noEventLoop(): static
    {
        return new static('via() needs an event loop. Boot IOPools, or call the blocking method.');
    }

    public static function noWorkTargets(): static
    {
        return new static('via() needs the IOPools work targets bound in the container (work-targets).');
    }

    public static function offloadTargetDiscardsResult(string $target): static
    {
        return new static("The [{$target}] work target hands back a queued job, not the call's result: via() cannot use it.");
    }

    /** One FTDI interface runs one engine: a UART, or MPSSE for I2C, SPI and DigitalIO. */
    public static function ftdiEngineBusy(string $device, string $held, string $wanted): static
    {
        return new static("FTDI device {$device} is open for {$held}: its one engine runs {$held} or {$wanted}, not both. Disconnect it first.");
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
