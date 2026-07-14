<?php

use ScrapyardIO\NutsAndBolts\Env;
use ScrapyardIO\NutsAndBolts\Reflection;

if (! function_exists('reflect_property')) {
    function reflect_property(object $object, string $attribute): ?ReflectionProperty
    {
        return Reflection::reflect_property($object, $attribute);
    }
}

if (! function_exists('reflect_class')) {
    /**
     * @throws ReflectionException
     */
    function reflect_class(object|string $class, string $attribute): ?ReflectionAttribute
    {
        return Reflection::reflect_class($class, $attribute);
    }
}

if (! function_exists('reflect_parameter')) {
    /**
     * @throws ReflectionException
     */
    function reflect_parameter(object|string $class, string $method, string $attribute): ?ReflectionParameter
    {
        return Reflection::reflect_parameter($class, $method, $attribute);
    }
}

if (! function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     */
    function class_basename(string|object $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (! function_exists('e')) {
    /**
     * Encode HTML special characters in a string.
     */
    function e(mixed $value, bool $double_encode = true): string
    {
        /*
        if ($value instanceof DeferringDisplayableValue) {
            $value = $value->resolveDisplayableValue();
        }*/

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $double_encode);
    }
}

if (! function_exists('windows_os')) {
    /**
     * Determine whether the current environment is Windows based.
     */
    function windows_os(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}

if (! function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     */
    function tap(mixed $value, ?callable $callback = null): mixed
    {
        if (is_null($callback)) {
            return new class($value) {
                public function __construct(public mixed $target)
                {
                }

                public function __call(string $method, array $parameters): mixed
                {
                    $this->target->{$method}(...$parameters);

                    return $this->target;
                }
            };
        }

        $callback($value);

        return $value;
    }
}

if (! function_exists('throw_unless')) {
    /**
     * Throw the given exception unless the given condition is true.
     */
    function throw_unless(mixed $condition, Throwable|string $exception = RuntimeException::class, mixed ...$parameters): mixed
    {
        if ($condition) {
            return $condition;
        }

        if (is_string($exception)) {
            $exception = new $exception(...$parameters);
        }

        throw $exception;
    }
}

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}


