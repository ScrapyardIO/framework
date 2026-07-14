<?php

namespace BareMetal\Chassis;

use Closure;
use BareMetal\Contracts\Chassis\ContextualAttribute;
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
     * From Arr::wrap() in Illuminate\Support.
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
     * From global value() helper in Illuminate\Support.
     */
    public static function unwrapIfClosure(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }

    /**
     * Get the class name of the given parameter's type, if possible.
     *
     * From Reflector::getParameterClassName() in Illuminate\Support.
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
     * Get the class names of the given parameter's type, including union types.
     *
     * @return list<class-string>|null
     */
    public static function getParameterClassNames(ReflectionParameter $parameter): ?array
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType && ! ($type instanceof \ReflectionUnionType)) {
            return null;
        }

        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];

        $names = [];

        foreach ($types as $namedType) {
            if (! $namedType instanceof ReflectionNamedType || $namedType->isBuiltin()) {
                continue;
            }

            $name = $namedType->getName();

            if (! is_null($class = $parameter->getDeclaringClass())) {
                if ($name === 'self') {
                    $names[] = $class->getName();

                    continue;
                }

                if ($name === 'parent' && $parent = $class->getParentClass()) {
                    $names[] = $parent->getName();

                    continue;
                }
            }

            $names[] = $name;
        }

        return $names === [] ? null : $names;
    }

    /**
     * Get a contextual attribute from a dependency.
     */
    public static function getContextualAttributeFromDependency(ReflectionParameter $dependency): ?ReflectionAttribute
    {
        return $dependency->getAttributes(ContextualAttribute::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
    }
}
