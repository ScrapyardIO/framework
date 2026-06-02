<?php

if (! function_exists('reflect_property')) {
    function reflect_property(object $object, string $attribute): ?ReflectionProperty
    {
        $reflection = new ReflectionClass($object);
        foreach ($reflection->getProperties() as $property) {
            $result = $property->getAttributes($attribute);
            if ($result) {
                return $property;
            }
        }

        return null;
    }
}

if (! function_exists('reflect_class')) {
    function reflect_class(object|string $class, string $attribute): ?ReflectionAttribute
    {
        $attributes = (new ReflectionClass($class))->getAttributes($attribute);

        return $attributes[0] ?? null;
    }
}

if (! function_exists('reflect_parameter')) {
    function reflect_parameter(object|string $class, string $method, string $attribute): ?ReflectionParameter
    {
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethod($method)->getParameters() as $parameter) {
            $result = $parameter->getAttributes($attribute);
            if ($result) {
                return $parameter;
            }
        }

        return null;
    }
}
