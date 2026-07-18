<?php

namespace Fabricate\Chassis;

use Closure;
use Fabricate\Contracts\Chassis\ContextualAttribute;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * @internal
 */
class Util
{
    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * From Arr::wrap() in Fabricate\NutsAndBolts.
     *
     * @param  mixed  $value
     * @return array
     */
    public static function arrayWrap(mixed $value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Return the default value of the given value.
     *
     * From global value() helper in Fabricate\NutsAndBolts.
     *
     * @param  mixed  $value
     * @param  mixed  ...$args
     * @return mixed
     */
    public static function unwrapIfClosure(mixed $value, ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }

    /**
     * Get the class name of the given parameter's type, if possible.
     *
     * From Reflector::getParameterClassName() in Fabricate\NutsAndBolts.
     *
     * @param ReflectionParameter $parameter
     * @return string|null
     */
    public static function getParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }

    /**
     * Get a contextual attribute from a dependency.
     *
     * @param ReflectionParameter $dependency
     * @return ReflectionAttribute|null
     */
    public static function getContextualAttributeFromDependency(ReflectionParameter $dependency): ?ReflectionAttribute
    {
        return $dependency->getAttributes(ContextualAttribute::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
    }
}
