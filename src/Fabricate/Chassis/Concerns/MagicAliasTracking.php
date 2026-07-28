<?php

namespace Fabricate\Chassis\Concerns;

use Closure;
use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use LogicException;
use ReflectionException;

trait MagicAliasTracking
{
    /**
     * The registered type aliases.
     *
     * @var string[]
     */
    protected array $aliases = [];

    /**
     * The registered aliases keyed by the abstract name.
     *
     * @var array[]
     */
    protected array $abstractAliases = [];

    /**
     * Remove an alias from the contextual binding alias cache.
     *
     * @param string $searched
     * @return void
     */
    protected function removeAbstractAlias(string $searched): void
    {
        if (! isset($this->aliases[$searched])) {
            return;
        }

        foreach ($this->abstractAliases as $abstract => $aliases) {
            foreach ($aliases as $index => $alias) {
                if ($alias == $searched) {
                    unset($this->abstractAliases[$abstract][$index]);
                }
            }
        }
    }

    /**
     * Determine if a given string is an alias.
     *
     * @param string $name
     * @return bool
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Get the alias for an abstract if available.
     *
     * @param string $abstract
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract])
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    /**
     * Get the extender callbacks for a given type.
     *
     * @param string $abstract
     * @return array
     */
    protected function getExtenders(string $abstract): array
    {
        return $this->extenders[$this->getAlias($abstract)] ?? [];
    }

    /**
     * Alias a type to a different name.
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     *
     * @throws LogicException
     */
    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }

        $this->removeAbstractAlias($alias);

        $this->aliases[$alias] = $abstract;

        $this->abstractAliases[$abstract][] = $alias;
    }

    /**
     * "Extend" an abstract type in the container.
     *
     * @param string $abstract
     * @param Closure|callable $closure
     * @return void
     * @throws ReflectionException|CircularDependencyException|BindingResolutionException
     *
     */
    public function extend($abstract, Closure|callable $closure): void
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);

            $this->rebound($abstract);
        } else {
            $this->extenders[$abstract][] = $closure;

            if ($this->resolved($abstract)) {
                $this->rebound($abstract);
            }
        }
    }
}