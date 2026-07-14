<?php

use BareMetal\Chassis\EntryNotFoundException;
use BareMetal\Config\Repository;
use BareMetal\Core\Machine;
use BareMetal\Chassis\Chassis;
use BareMetal\Contracts\Chassis\BindingResolutionException;
use BareMetal\Contracts\Chassis\CircularDependencyException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @template TClass of object
     *
     * @param string|class-string<TClass>|null $abstract
     * @return ($abstract is class-string<TClass> ? TClass : ($abstract is null ? Machine : mixed))
     * @throws CircularDependencyException
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        if (is_null($abstract)) {
            return Chassis::getInstance();
        }

        return Chassis::getInstance()->make($abstract, $parameters);
    }
}

if (! function_exists('base_path')) {
    /**
     * Get the path to the base of the installation.
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
     * @param  (callable(\Throwable): TFallback)|TFallback  $rescue
     * @param  bool|callable(\Throwable): bool  $report
     * @return TValue|TFallback
     */
    function rescue(callable $callback, $rescue = null, $report = true)
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
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    function report(Throwable|string $exception): void
    {
        if (is_string($exception)) {
            $exception = new Exception($exception);
        }

        app(\BareMetal\Contracts\Debug\ExceptionHandler::class)->report($exception);
    }
}

if (! function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     * @throws BindingResolutionException|CircularDependencyException|ReflectionException
     */
    function storage_path(string $path = ''): string
    {
        return app()->storagePath($path);
    }
}

if (! function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     * @throws BindingResolutionException|CircularDependencyException|ReflectionException
     */
    function app_path(string $path = ''): string
    {
        return app()->path($path);
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
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     * @throws EntryNotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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

