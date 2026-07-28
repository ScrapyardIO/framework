<?php

use Fabricate\Core\Machine;
use Fabricate\Chassis\Chassis;
use Fabricate\Contracts\Debug\ExceptionHandler;
use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Chassis\CircularDependencyException;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @template TClass of object
     *
     * @param string|class-string<TClass>|null $abstract
     * @return ($abstract is class-string<TClass> ? TClass : ($abstract is null ? Machine : mixed))
     * @throws ReflectionException|CircularDependencyException|BindingResolutionException
     */
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        if (is_null($abstract)) {
            return Chassis::getInstance();
        }

        return Chassis::getInstance()->make($abstract, $parameters);
    }
}

if (! function_exists('decrypt')) {
    /**
     * Decrypt the given value.
     *
     * @param string $value
     * @param bool $unserialize
     * @return mixed
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
     */
    function decrypt(string $value, bool $unserialize = true): mixed
    {
        return app('encrypter')->decrypt($value, $unserialize);
    }
}


if (! function_exists('base_path')) {
    /**
     * Get the path to the base of the installation.
     *
     * @param string $path
     * @return string
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
     */
    function base_path(string $path = ''): string
    {
        return app()->basePath($path);
    }
}

if (! function_exists('rescue')) {
    /**
     * Catch a potential exception and return a default value.
     *
     * @template TValue
     * @template TFallback
     *
     * @param  callable(): TValue  $callback
     * @param (callable(\Throwable): TFallback)|null $rescue
     * @param callable(\Throwable): bool|bool $report
     * @return TValue|TFallback
     */
    function rescue(callable $callback, ?callable $rescue = null, callable|bool $report = true)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            if (value($report, $e)) {
                report($e);
            }

            return value($rescue, $e);
        }
    }
}

if (! function_exists('report')) {
    /**
     * Report an exception.
     *
     * @param Throwable|string $exception
     * @throws Throwable
     */
    function report(Throwable|string $exception): void
    {
        if (is_string($exception)) {
            $exception = new Exception($exception);
        }

        app(ExceptionHandler::class)->report($exception);
    }
}

if (! function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     *
     * @param string $path
     * @return string
     */
    function app_path(string $path = ''): string
    {
        return app()->path($path);
    }
}

if (! function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param string $path
     * @return string
     */
    function config_path(string $path = ''): string
    {
        return app()->configPath($path);
    }
}

if (! function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param string $path
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return app()->storagePath($path);
    }
}

if (! function_exists('cache')) {
    /**
     * Get / set the specified cache value.
     *
     * If an array is passed, we will assume you want to set an array of values.
     *
     * @param  string|array<string, mixed>|null  $key
     * @param  mixed  $default
     * @return ($key is null ? \Fabricate\Cache\CacheManager : ($key is string ? mixed : bool))
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
     */
    function cache(array|string|null $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return app('cache');
        }

        if (is_string($key)) {
            return app('cache')->get($key, $default);
        }

        if (! is_array($key)) {
            throw new InvalidArgumentException(
                'When setting a value in the cache, you must pass an array of key / value pairs.'
            );
        }

        return app('cache')->put(array_key_first($key), reset($key), $default);
    }
}

if (! function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param string|array<string, mixed>|null $key
     * @param mixed|null $default
     * @return ($key is null ? Repository : ($key is string ? mixed : null))
     * @throws EntryNotFoundException|CircularDependencyException
     * @throws NotFoundExceptionInterface|ContainerExceptionInterface
     */
    function config(array|string|null $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return app('config');
        }

        if (is_array($key)) {
            return app('config')->set($key);
        }

        return app('config')->get($key, $default);
    }
}

if (! function_exists('logger')) {
    /**
     * Log a debug message to the logs.
     *
     * @param  string|null  $message
     * @param  array  $context
     * @return ($message is null ? \Psr\Log\LoggerInterface : null)
     */
    function logger(?string $message = null, array $context = []): ?LoggerInterface
    {
        if (is_null($message)) {
            return app('log');
        }

        app('log')->debug($message, $context);

        return null;
    }
}

if (! function_exists('logs')) {
    /**
     * Get a log driver instance.
     *
     * @param  string|null  $driver
     * @return ($driver is null ? \Fabricate\Log\LogManager : \Psr\Log\LoggerInterface)
     */
    function logs(?string $driver = null): LoggerInterface|LogManager
    {
        return $driver ? app('log')->driver($driver) : app('log');
    }
}
