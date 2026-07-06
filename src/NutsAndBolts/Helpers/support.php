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
