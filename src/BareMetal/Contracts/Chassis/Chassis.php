<?php

namespace BareMetal\Contracts\Chassis;

use LogicException;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

interface Chassis extends ContainerInterface
{
    /**
     * {@inheritdoc}
     *
     * @template TClass of object
     *
     * @param  string|class-string<TClass>  $id
     * @return ($id is class-string<TClass> ? TClass : mixed)
     */
    public function get(string $id): mixed;

    /**
     * Determine if the given abstract type has been bound.
     */
    public function bound(string $abstract): bool;

    /**
     * Alias a type to a different name.
     * @throws LogicException
     */
    public function alias(string $abstract, string $alias): void;

    /**
     * Assign a set of tags to a given binding.
     */
    public function tag(array|string $abstracts, mixed $tags): void;

    /**
     * Resolve every binding for a given tag.
     */
    public function tagged(string $tag): iterable;

    /**
     * Register a binding with the container.
     */
    public function bind(callable|string $abstract, callable|string|null $concrete = null, bool $shared = false): void;

    /**
     * Bind a callback to resolve with Container::call.
     */
    public function bindMethod(array|string $method, callable $callback): void;

    /**
     * Register a binding if it hasn't already been registered.
     */
    public function bindIf(callable|string $abstract, callable|string|null $concrete = null, bool $shared = false): void;

    /**
     * Register a shared binding in the container.
     */
    public function singleton(callable|string $abstract, callable|string|null $concrete = null): void;

    /**
     * Register a shared binding if it hasn't already been registered.
     */
    public function singletonIf(callable|string $abstract, callable|string|null $concrete = null): void;

    /**
     * Register a scoped binding in the container.
     */
    public function scoped(callable|string $abstract, callable|string|null $concrete = null): void;

    /**
     * Register a scoped binding if it hasn't already been registered.
     */
    public function scopedIf(callable|string $abstract, callable|string|null $concrete = null): void;

    /**
     * "Extend" an abstract type in the container.
     *
     * @throws InvalidArgumentException
     */
    public function extend(callable|string $abstract, callable $closure): void;

    /**
     * Register an existing instance as shared in the container.
     */
    public function instance(callable|string $abstract, mixed $instance): mixed;

    /**
     * Add a contextual binding to the container.
     */
    public function addContextualBinding(string $concrete, callable|string $abstract, callable|string $implementation): void;

    /**
     * Define a contextual binding.
     *
     * @param  string|array  $concrete
     * @return ContextualBindingBuilder
     */
    public function when(string|array $concrete): ContextualBindingBuilder;

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param  string|class-string<TClass>  $abstract
     * @return ($abstract is class-string<TClass> ? callable(): TClass : callable(): mixed)
     */
    public function factory(string $abstract): mixed;

    /**
     * Flush the container of all bindings and resolved instances.
     */
    public function flush(): void;

    /**
     * Resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param  string|class-string<TClass>  $abstract
     * @param  array  $parameters
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws BindingResolutionException
     */
    public function make(string $abstract, array $parameters = []): mixed;

    /**
     * Call the given callable / class@method and inject its dependencies.
     */
    public function call(callable|string $callback, array $parameters = [], ?string $defaultMethod = null): mixed;

    /**
     * Determine if the given abstract type has been resolved.
     */
    public function resolved(string $abstract): bool;

    /**
     * Register a new before resolving callback.
     */
    public function beforeResolving(callable|string $abstract, ?callable $callback = null): void;

    /**
     * Register a new resolving callback.
     */
    public function resolving(callable|string$abstract, ?callable $callback = null): void;

    /**
     * Register a new after resolving callback.
     */
    public function afterResolving(callable|string $abstract, ?callable $callback = null): void;
}
