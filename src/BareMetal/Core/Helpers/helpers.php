<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @template TClass of object
     *
     * @param string|class-string<TClass>|null $abstract
     * @return ($abstract is class-string<TClass> ? TClass : ($abstract is null ? BareMetal\Core\Scrapyard : mixed))
     * @throws BindingResolutionException
     */
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}

if (! function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     * @throws BindingResolutionException
     */
    function storage_path(string $path = ''): string
    {
        return app()->storagePath($path);
    }
}
