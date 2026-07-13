<?php

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

if (! function_exists('join_paths')) {
    /**
     * Join the given paths together.
     */
    function join_paths(?string $base_path, string ...$paths): string
    {
        foreach ($paths as $index => $path) {
            if (empty($path) && $path !== '0') {
                unset($paths[$index]);
            } else {
                $paths[$index] = DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
            }
        }

        return $base_path.implode('', $paths);
    }
}
