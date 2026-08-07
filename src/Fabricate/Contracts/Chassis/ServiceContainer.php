<?php

namespace Fabricate\Contracts\Chassis;

use ArrayAccess;
use Fabricate\NutsAndBolts\ServiceProvider;
use Psr\Container\ContainerInterface;

/**
 * Public consumer container surface.
 *
 * Enough for MagicAlias, Manager, and ServiceProvider container ops.
 * Full binding API lives on Chassis's WireframeServiceContainer.
 */
interface ServiceContainer extends ContainerInterface, ArrayAccess
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
     * Resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param string|class-string<TClass> $abstract
     * @param array<string, mixed> $parameters
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     */
    public function make(string $abstract, array $parameters = []): mixed;

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param callable|string $callback
     * @param array<string, mixed> $parameters
     * @param string|null $defaultMethod
     * @return mixed
     */
    public function call(callable|string $callback, array $parameters = [], ?string $defaultMethod = null): mixed;

    /**
     * Register an existing instance as shared in the container.
     *
     * @template TInstance of mixed
     *
     * @param callable|string $abstract
     * @param TInstance $instance
     * @return TInstance
     */
    public function instance(callable|string $abstract, mixed $instance): mixed;

    /**
     * Determine if the given abstract type has been resolved.
     *
     * @param string $abstract
     * @return bool
     */
    public function resolved(string $abstract): bool;

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool;

    /**
     * Register a new after resolving callback.
     *
     * @param callable|string $abstract
     * @param callable|null $callback
     * @return void
     */
    public function afterResolving(callable|string $abstract, ?callable $callback = null): void;

    /**
     * Set the callback which determines the current container environment.
     *
     * Used by contextual bindings / attributes and by Core after LoadConfiguration
     * (`resolveEnvironmentUsing($program->environment(...))`).
     *
     * @param (callable(array<int, string>|string): (bool|string))|string|null $callback
     * @return void
     */
    public function resolveEnvironmentUsing(callable|string|null $callback): void;

    /**
     * Class name of the CLI machine (Workshop) implementation.
     *
     * Chassis throws; Machine overrides.
     *
     * @return class-string
     */
    public function cliMachine(): string;

    /**
     * Determine if the application is running with debug mode enabled.
     *
     * Chassis throws; Machine overrides.
     *
     * @return bool
     */
    public function hasDebugModeEnabled(): bool;

    /**
     * Register a service provider with the application.
     *
     * Chassis throws; Machine overrides.
     *
     * @param string|ServiceProvider $provider
     * @param bool $force
     * @return ServiceProvider
     */
    public function register(string|ServiceProvider $provider, bool $force = false): ServiceProvider;

    /**
     * Resolve a service provider instance from the class name.
     *
     * Chassis throws; Machine overrides.
     *
     * @param string $provider
     * @return ServiceProvider
     */
    public function resolveProvider(string $provider): ServiceProvider;
}
