<?php

namespace Fabricate\Chassis\Concerns;

use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use ReflectionException;

trait Singletons
{
    /**
     * The container's shared instances.
     *
     * @var object[]
     */
    protected array $instances = [];

    /**
     * The container's scoped instances.
     *
     * @var array
     */
    protected array $scopedInstances = [];

    /**
     * Register a scoped binding in the container.
     *
     * @param Closure|string $abstract
     * @param Closure|string|null $concrete
     * @return void
     * @throws ReflectionException|CircularDependencyException|BindingResolutionException
 */
    public function scoped($abstract, $concrete = null): void
    {
        $this->scopedInstances[] = $abstract;

        $this->singleton($abstract, $concrete);
    }

    /**
     * Register a shared binding in the container.
     *
     * @param Closure|string $abstract
     * @param Closure|string|null $concrete
     * @return void
     * @throws ReflectionException|CircularDependencyException|BindingResolutionException
 */
    public function singleton($abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register a shared binding if it hasn't already been registered.
     *
     * @param Closure|string $abstract
     * @param Closure|string|null $concrete
     * @return void
     * @throws ReflectionException|CircularDependencyException|BindingResolutionException
 */
    public function singletonIf($abstract, $concrete = null): void
    {
        if (! $this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
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

    /**
     * Register a scoped binding if it hasn't already been registered.
     *
     * @param Closure|string $abstract
     * @param Closure|string|null $concrete
     * @return void
     * @throws ReflectionException|CircularDependencyException|BindingResolutionException
    */
    public function scopedIf($abstract, $concrete = null): void
    {
        if (! $this->bound($abstract)) {
            $this->scoped($abstract, $concrete);
        }
    }

}