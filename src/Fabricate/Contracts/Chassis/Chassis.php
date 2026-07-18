<?php

namespace Fabricate\Contracts\Chassis;

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
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool;

    /**
     * Alias a type to a different name.
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     *
     * @throws LogicException
     */
    public function alias(string $abstract, string $alias): void;

    /**
     * Assign a set of tags to a given binding.
     *
     * @param array|string $abstracts
     * @param mixed ...$tags
     * @return void
     */
    public function tag(array|string $abstracts, mixed ...$tags): void;

    /**
     * Resolve every binding for a given tag.
     *
     * @param string $tag
     * @return iterable
     */
    public function tagged(string $tag): iterable;

    /**
     * Register a binding with the container.
     *
     * @param callable|string $abstract
     * @param callable|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(callable|string $abstract, callable|string|null $concrete = null, bool $shared = false): void;

    /**
     * Bind a callback to resolve with Container::call.
     *
     * @param array|string $method
     * @param callable $callback
     * @return void
     */
    public function bindMethod(array|string $method, callable $callback): void;

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param callable|string $abstract
     * @param callable|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bindIf(callable|string $abstract, callable|string|null $concrete = null, bool $shared = false): void;

    /**
     * Register a shared binding in the container.
     *
     * @param callable|string $abstract
     * @param callable|string|null $concrete
     * @return void
     */
    public function singleton(callable|string $abstract, callable|string|null $concrete = null): void;

    /**
     * Register a shared binding if it hasn't already been registered.
     *
     * @param callable|string $abstract
     * @param callable|string|null $concrete
     * @return void
     */
    public function singletonIf(callable|string $abstract, callable|string|null $concrete = null): void;

    /**
     * Register a scoped binding in the container.
     *
     * @param callable|string $abstract
     * @param callable|string|null $concrete
     * @return void
     */
    public function scoped(callable|string $abstract, callable|string|null $concrete = null): void;

    /**
     * Register a scoped binding if it hasn't already been registered.
     *
     * @param callable|string $abstract
     * @param callable|string|null $concrete
     * @return void
     */
    public function scopedIf(callable|string $abstract, callable|string|null $concrete = null): void;

    /**
     * "Extend" an abstract type in the container.
     *
     * @param callable|string $abstract
     * @param  callable  $closure
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function extend(callable|string $abstract, callable $closure): void;

    /**
     * Register an existing instance as shared in the container.
     *
     * @template TInstance of mixed
     *
     * @param callable|string $abstract
     * @param  TInstance  $instance
     * @return TInstance
     */
    public function instance(callable|string $abstract, mixed $instance): mixed;

    /**
     * Add a contextual binding to the container.
     *
     * @param string $concrete
     * @param callable|string $abstract
     * @param callable|string $implementation
     * @return void
     */
    public function addContextualBinding(string $concrete, callable|string $abstract, callable|string $implementation): void;

    /**
     * Define a contextual binding.
     *
     * @param array|string $concrete
     * @return ContextualBindingBuilder
     */
    public function when(array|string $concrete): ContextualBindingBuilder;

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param string|class-string<TClass> $abstract
     * @return ($abstract is class-string<TClass> ? callable(): TClass : callable(): mixed)
     */
    public function factory(string $abstract): callable;

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush(): void;

    /**
     * Resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param string|class-string<TClass> $abstract
     * @param  array  $parameters
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws BindingResolutionException
     */
    public function make(string $abstract, array $parameters = []): mixed;

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param callable|string $callback
     * @param  array  $parameters
     * @param string|null $defaultMethod
     * @return mixed
     */
    public function call(callable|string $callback, array $parameters = [], ?string $defaultMethod = null): mixed;

    /**
     * Determine if the given abstract type has been resolved.
     *
     * @param string $abstract
     * @return bool
     */
    public function resolved(string $abstract): bool;

    /**
     * Register a new before resolving callback.
     *
     * @param callable|string $abstract
     * @param  callable|null  $callback
     * @return void
     */
    public function beforeResolving(callable|string $abstract, ?callable $callback = null): void;

    /**
     * Register a new resolving callback.
     *
     * @param callable|string $abstract
     * @param  callable|null  $callback
     * @return void
     */
    public function resolving(callable|string $abstract, ?callable $callback = null): void;

    /**
     * Register a new after resolving callback.
     *
     * @param callable|string $abstract
     * @param  callable|null  $callback
     * @return void
     */
    public function afterResolving(callable|string $abstract, ?callable $callback = null): void;
}
