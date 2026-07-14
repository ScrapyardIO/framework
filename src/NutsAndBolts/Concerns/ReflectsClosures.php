<?php

namespace ScrapyardIO\NutsAndBolts\Concerns;

use BareMetal\Chassis\Util;
use Closure;
use ReflectionException;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionUnionType;
use RuntimeException;
use ScrpayardIO\NutsAndBolts\Collection;

trait ReflectsClosures
{
    /**
     * Get the class name of the first parameter of the given Closure.
     *
     * @throws ReflectionException
     * @throws RuntimeException
     */
    protected function firstClosureParameterType(Closure $closure): string
    {
        $types = array_values($this->closureParameterTypes($closure));

        if (! $types) {
            throw new RuntimeException('The given Closure has no parameters.');
        }

        if ($types[0] === null) {
            throw new RuntimeException('The first parameter of the given Closure is missing a type hint.');
        }

        return $types[0];
    }

    /**
     * Get the class names of the first parameter of the given Closure, including union types.
     *
     * @return list<class-string>
     *
     * @throws ReflectionException
     * @throws RuntimeException
     */
    protected function firstClosureParameterTypes(Closure $closure): array
    {
        $reflection = new ReflectionFunction($closure);

        $types = (new Collection($reflection->getParameters()))
            ->mapWithKeys(function ($parameter) {
                if ($parameter->isVariadic()) {
                    return [$parameter->getName() => null];
                }

                return [$parameter->getName() => Util::getParameterClassNames($parameter)];
            })
            ->filter()
            ->values()
            ->all();

        if (empty($types)) {
            throw new RuntimeException('The given Closure has no parameters.');
        }

        if (isset($types[0]) && empty($types[0])) {
            throw new RuntimeException('The first parameter of the given Closure is missing a type hint.');
        }

        return $types[0];
    }

    /**
     * Get the class names / types of the parameters of the given Closure.
     *
     * @return array<string, class-string|null>
     *
     * @throws ReflectionException
     */
    protected function closureParameterTypes(Closure $closure): array
    {
        $reflection = new ReflectionFunction($closure);

        return (new Collection($reflection->getParameters()))
            ->mapWithKeys(function ($parameter) {
                if ($parameter->isVariadic()) {
                    return [$parameter->getName() => null];
                }

                return [$parameter->getName() => Util::getParameterClassName($parameter)];
            })
            ->all();
    }

    /**
     * Get the class names / types of the return type of the given Closure.
     *
     * @return list<class-string>
     *
     * @throws ReflectionException
     */
    protected function closureReturnTypes(callable $closure): array
    {
        $reflection = new ReflectionFunction($closure);

        if ($reflection->getReturnType() === null ||
            $reflection->getReturnType() instanceof ReflectionIntersectionType) {
            return [];
        }

        $types = $reflection->getReturnType() instanceof ReflectionUnionType
            ? $reflection->getReturnType()->getTypes()
            : [$reflection->getReturnType()];

        return (new Collection($types))
            ->reject(fn ($type) => $type->isBuiltin())
            ->reject(fn ($type) => in_array($type->getName(), ['static', 'self']))
            ->map(fn ($type) => $type->getName())
            ->values()
            ->all();
    }
}
