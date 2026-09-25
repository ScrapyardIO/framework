<?php

namespace GeneralPurposeIO\Contracts\IntegratedCircuits;

use GeneralPurposeIO\Contracts\Core\GPIOLevelException;
use Throwable;

class CircuitException extends GPIOLevelException
{
    public static function notRegistered(string $slug): static
    {
        return new static("Circuit [{$slug}] is not registered.");
    }

    public static function noCircuitConfig(string $slug): static
    {
        return new static("Circuit [{$slug}] has no config at circuits.{$slug}. Publish the chip package's config into config/circuits/{$slug}.php.");
    }

    public static function noDefaultConfig(string $slug): static
    {
        return new static("circuits.{$slug} names no default_config, and none was asked for.");
    }

    public static function noSuchConfig(string $slug, string $config): static
    {
        return new static("circuits.{$slug}.configs has no [{$config}].");
    }

    public static function noSuchFactory(string $slug, string $class, string $protocol, ?Throwable $previous = null): static
    {
        return new static("Circuit [{$slug}] class [{$class}] has no static protocol factory [{$protocol}].", previous: $previous);
    }

    public static function factoryNotPublicStatic(string $slug, string $protocol): static
    {
        return new static("Circuit [{$slug}] protocol factory [{$protocol}] must be a public static method.");
    }

    public static function missingParameter(string $slug, string $protocol, string $parameter): static
    {
        return new static("Circuit [{$slug}] protocol [{$protocol}] is missing required parameter [{$parameter}].");
    }

    public static function factoryReturnedNonCircuit(string $slug, string $protocol): static
    {
        return new static("Circuit [{$slug}] protocol [{$protocol}] must return an IntegratedCircuit instance.");
    }
}
