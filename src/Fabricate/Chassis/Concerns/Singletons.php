<?php

namespace Fabricate\Chassis\Concerns;

use Fabricate\Chassis\Exceptions\BindingResolutionException;
use Fabricate\Chassis\Exceptions\CircularDependencyException;
use ReflectionException;

trait Singletons
{
    /**
     * The container's shared instances.
     *
     * @var array<string, mixed>
     */
    protected array $instances = [];

    /**
     * The container's scoped instances.
     *
     * @var list<callable|string>
     */
    protected array $scopedInstances = [];

    /**
     * Register a scoped binding in the container.
     *
     * @param callable|string $abstract
     * @param callable|string|null $concrete
     * @return void
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function scoped(callable|string $abstract, callable|string|null $concrete = null): void
    {
        $this->scopedInstances[] = $abstract;

        $this->singleton($abstract, $concrete);
    }

    /**
     * Register a shared binding in the container.
     *
     * @param callable|string $abstract
     * @param callable|string|null $concrete
     * @return void
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function singleton(callable|string $abstract, callable|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register a shared binding if it hasn't already been registered.
     *
     * @param callable|string $abstract
     * @param callable|string|null $concrete
     * @return void
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function singletonIf(callable|string $abstract, callable|string|null $concrete = null): void
    {
        if (! $this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    /**
     * Register a scoped binding if it hasn't already been registered.
     *
     * @param callable|string $abstract
     * @param callable|string|null $concrete
     * @return void
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function scopedIf(callable|string $abstract, callable|string|null $concrete = null): void
    {
        if (! $this->bound($abstract)) {
            $this->scoped($abstract, $concrete);
        }
    }

    /**
     * Clear all of the scoped instances from the container.
     *
     * @return void
     */
    public function forgetScopedInstances(): void
    {
        foreach ($this->scopedInstances as $scoped) {
            unset($this->instances[$scoped]);
        }
    }

    /**
     * Drop every stale instances and aliases.
     *
     * @param string $abstract
     * @return void
     */
    protected function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }
}
