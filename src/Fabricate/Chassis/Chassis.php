<?php

namespace Fabricate\Chassis;

use Closure;
use Exception;
use Fabricate\Chassis\Attributes\Scoped;
use Fabricate\Chassis\Attributes\Singleton;
use Fabricate\Chassis\Concerns\Bindings;
use Fabricate\Chassis\Concerns\CallbackManagement;
use Fabricate\Chassis\Concerns\Singletons;
use Fabricate\Chassis\Exceptions\BindingResolutionException;
use Fabricate\Chassis\Exceptions\EntryNotFoundException;
use Fabricate\Chassis\Contracts\WireframeServiceContainer;
use Fabricate\Chassis\Exceptions\CircularDependencyException;
use Fabricate\Contracts\Config\Repository;
use Fabricate\NutsAndBolts\Concerns\ReflectsClosures;
use Fabricate\NutsAndBolts\ServiceProvider;
use BadMethodCallException;
use InvalidArgumentException;
use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * The Fabricate service container.
 *
 * @property-read Repository $config
 */
class Chassis implements WireframeServiceContainer
{
    use Bindings, ReflectsClosures, CallbackManagement, Singletons;

    /**
     * The current globally available service container (if any).
     *
     * @var static|null
     */
    protected static ?WireframeServiceContainer $container = null;

    /**
     * The registered type aliases.
     *
     * @var array<string, string>
     */
    protected array $aliases = [];

    /**
     * The registered aliases keyed by the abstract name.
     *
     * @var array<string, list<string>>
     */
    protected array $abstractAliases = [];

    /**
     * An array of the types that have been resolved.
     *
     * @var array<string, bool>
     */
    protected array $resolved = [];

    /**
     * The stack of concretions currently being built.
     *
     * @var list<string>
     */
    protected array $buildStack = [];

    /**
     * The parameter override stack.
     *
     * @var list<array<string, mixed>>
     */
    protected array $with = [];

    /**
     * The extension closures for services.
     *
     * @var array<string, list<callable>>
     */
    protected array $extenders = [];

    /**
     * Every registered tag.
     *
     * @var array<string, list<string>>
     */
    protected array $tags = [];

    /**
     * The callback used to determine the container's environment.
     *
     * @var (callable(array<int, string>|string): bool|string)|null
     */
    protected $environmentResolver = null;

    /**
     * Dynamically access container services.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     *
     * @param string $key
     * @param  mixed  $value
     * @return void
     */
    public function __set(string $key, mixed $value)
    {
        $this[$key] = $value;
    }

    /**
     * {@inheritdoc}
     *
     * @template TClass of object
     *
     * @param  string|class-string<TClass>  $id
     * @return ($id is class-string<TClass> ? TClass : mixed)
     *
     * @throws CircularDependencyException
     * @throws EntryNotFoundException
     */
    public function get(string $id): mixed
    {
        try {
            return $this->resolve($id);
        } catch (Exception $e) {
            if ($this->has($id) || $e instanceof CircularDependencyException) {
                throw $e;
            }

            throw new EntryNotFoundException($id, is_int($e->getCode()) ? $e->getCode() : 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    /**
     * Get the value at a given offset.
     *
     * @param string $offset
     * @throws BindingResolutionException|CircularDependencyException|ReflectionException
     */
    public function offsetGet($offset): mixed
    {
        return $this->make($offset);
    }

    /**
     * Set the value at a given offset.
     *
     * @param string $offset
     * @param mixed $value
     * @throws BindingResolutionException|CircularDependencyException|ReflectionException
     */
    public function offsetSet($offset, mixed $value): void
    {
        $this->bind($offset, $value instanceof Closure ? $value : fn () => $value);
    }

    /**
     * Resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param string|class-string<TClass> $abstract
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws BindingResolutionException|CircularDependencyException|ReflectionException
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string  $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->bindings[$offset], $this->instances[$offset], $this->resolved[$offset]);
    }

    /**
     * Determine if a given offset exists.
     *
     * @param  string  $offset
     */
    public function offsetExists($offset): bool
    {
        return $this->bound($offset);
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            $this->isAlias($abstract);
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
     * @param callable|string $abstract
     * @param callable $closure
     * @return void
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function extend(callable|string $abstract, callable $closure): void
    {
        $abstract = is_string($abstract) ? $this->getAlias($abstract) : $abstract;

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
     * Get the extender callbacks for a given type.
     *
     * @param string $abstract
     * @return list<callable>
     */
    protected function getExtenders(string $abstract): array
    {
        return $this->extenders[$this->getAlias($abstract)] ?? [];
    }

    /**
     * Determine if the given abstract type has been resolved.
     *
     * @param string $abstract
     * @return bool
     */
    public function resolved(string $abstract): bool
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        return isset($this->resolved[$abstract]) ||
            isset($this->instances[$abstract]);
    }

    /**
     * Set the callback which determines the current container environment.
     *
     * @param (callable(array<int, string>|string): (bool|string))|string|null $callback
     * @return void
     */
    public function resolveEnvironmentUsing(callable|string|null $callback): void
    {
        $this->environmentResolver = $callback;
    }

    /**
     * Determine the environment for the container.
     *
     * @param string|array<int, string> $environments
     * @return bool
     */
    public function currentEnvironmentIs(array|string $environments): bool
    {
        return $this->environmentResolver === null
            ? false
            : call_user_func($this->environmentResolver, $environments);
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param callable|string $callback
     * @param array<string, mixed> $parameters
     * @param string|null $defaultMethod
     * @return mixed
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
     */
    public function call(callable|string $callback, array $parameters = [], ?string $defaultMethod = null): mixed
    {
        $pushedToBuildStack = false;

        if (($className = $this->getClassForCallable($callback)) && ! in_array(
                $className,
                $this->buildStack,
                true
            )) {
            $this->buildStack[] = $className;

            $pushedToBuildStack = true;
        }

        $result = BoundMethod::call($this, $callback, $parameters, $defaultMethod);

        if ($pushedToBuildStack) {
            array_pop($this->buildStack);
        }

        return $result;
    }

    /**
     * Resolve a dependency based on an attribute.
     *
     * @param ReflectionAttribute $attribute
     * @param ReflectionParameter $parameter
     * @return mixed
     *
     * @throws BindingResolutionException
     */
    public function resolveFromAttribute(ReflectionAttribute $attribute, ReflectionParameter $parameter): mixed
    {
        $handler = $this->contextualAttributes[$attribute->getName()] ?? null;

        $instance = $attribute->newInstance();

        if (is_null($handler) && method_exists($instance, 'resolve')) {
            $handler = $instance->resolve(...);
        }

        if (is_null($handler)) {
            throw new BindingResolutionException("Contextual binding attribute [{$attribute->getName()}] has no registered handler.");
        }

        return $handler($instance, $this, $parameter);
    }

    /**
     * Get the last parameter override.
     *
     * @return array
     */
    protected function getLastParameterOverride(): array
    {
        return count($this->with) ? array_last($this->with) : [];
    }

    /**
     * Determine if the given dependency has a parameter override.
     *
     * @param ReflectionParameter $dependency
     * @return bool
     */
    protected function hasParameterOverride(ReflectionParameter $dependency): bool
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }

    /**
     * Get a parameter override for a dependency.
     *
     * @param ReflectionParameter $dependency
     * @return mixed
     */
    protected function getParameterOverride(ReflectionParameter $dependency): mixed
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    /**
     * Resolve a non-class hinted primitive dependency.
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     *
     * @throws BindingResolutionException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter): mixed
    {
        if (! is_null($concrete = $this->getContextualConcrete('$'.$parameter->getName()))) {
            return Util::unwrapIfClosure($concrete, $this);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isVariadic()) {
            return [];
        }

        if ($parameter->hasType() && $parameter->allowsNull()) {
            return null;
        }

        $this->unresolvablePrimitive($parameter);
    }

    /**
     * Throw an exception for an unresolvable primitive.
     *
     * @param ReflectionParameter $parameter
     * @return void
     *
     * @throws BindingResolutionException
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter): void
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    /**
     * Resolve a class based variadic dependency from the container.
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    protected function resolveVariadicClass(ReflectionParameter $parameter): mixed
    {
        $className = Util::getParameterClassName($parameter);

        $abstract = $this->getAlias($className);

        if (! is_array($concrete = $this->getContextualConcrete($abstract))) {
            return $this->make($className);
        }

        return array_map(fn ($abstract) => $this->resolve($abstract), $concrete);
    }

    /**
     * Resolve a class based dependency from the container.
     *
     * @param ReflectionParameter $parameter
     * @param string|null $className
     * @return mixed
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException|ReflectionException
     */
    protected function resolveClass(ReflectionParameter $parameter, ?string $className = null): mixed
    {
        $className ??= Util::getParameterClassName($parameter);

        // First we will check if a default value has been defined for the parameter.
        // If it has, and no explicit binding exists, we should return it to avoid
        // overriding any of the developer specified defaults for the parameters.
        if ($parameter->isDefaultValueAvailable() &&
            ! $this->bound($className) &&
            $this->findInContextualBindings($className) === null) {
            return $parameter->getDefaultValue();
        }

        try {
            return $parameter->isVariadic()
                ? $this->resolveVariadicClass($parameter)
                : $this->make($className);
        }

            // If we can not resolve the class instance, we will check to see if the value
            // is variadic. If it is, we will return an empty array as the value of the
            // dependency similarly to how we handle scalar values in this situation.
        catch (BindingResolutionException $e) {
            if ($parameter->isVariadic()) {
                array_pop($this->with);

                return [];
            }

            throw $e;
        }
    }

    /**
     * Resolve every dependency from the ReflectionParameters.
     *
     * @param ReflectionParameter[] $dependencies
     * @return array
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    protected function resolveDependencies(array $dependencies): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If the dependency has an override for this particular build we will use
            // that instead as the value. Otherwise, we will continue with this run
            // of resolutions and let reflection attempt to determine the result.
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);

                continue;
            }

            $result = null;

            if (! is_null($attribute = Util::getContextualAttributeFromDependency($dependency))) {
                $result = $this->resolveFromAttribute($attribute, $dependency);
            }

            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
            $result ??= is_null($className = Util::getParameterClassName($dependency))
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency, $className);

            $this->fireAfterResolvingAttributeCallbacks($dependency->getAttributes(), $result);

            if ($dependency->isVariadic()) {
                $results = array_merge($results, $result);
            } else {
                $results[] = $result;
            }
        }

        return $results;
    }


    /**
     * Resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param callable|string|class-string<TClass> $abstract
     * @param array $parameters
     * @param bool $raiseEvents
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    protected function resolve(callable|string $abstract, array $parameters = [], bool $raiseEvents = true): mixed
    {
        $abstract = $this->getAlias($abstract);

        // First we'll fire any event handlers which handle the "before" resolving of
        // specific types. This gives some hooks the chance to add various extends
        // calls to change the resolution of objects that they're interested in.
        if ($raiseEvents) {
            $this->fireBeforeResolvingCallbacks($abstract, $parameters);
        }

        $concrete = $this->getContextualConcrete($abstract);

        $needsContextualBuild = ! empty($parameters) || ! is_null($concrete);

        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
        if (isset($this->instances[$abstract]) && ! $needsContextualBuild) {
            return $this->instances[$abstract];
        }

        $this->with[] = $parameters;

        if (is_null($concrete)) {
            $concrete = $this->getConcrete($abstract);
        }

        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.
        $object = $this->isBuildable($concrete, $abstract)
            ? $this->build($concrete)
            : $this->make($concrete);

        // If we defined any extenders for this type, we'll need to spin through them
        // and apply them to the object being built. This allows for the extension
        // of services, such as changing configuration or decorating the object.
        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }

        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
        if ($this->isShared($abstract) && ! $needsContextualBuild) {
            $this->instances[$abstract] = $object;
        }

        if ($raiseEvents) {
            $this->fireResolvingCallbacks($abstract, $object);
        }

        // Before returning, we will also set the resolved flag to "true" and pop off
        // the parameter overrides for this build. After those two things are done
        // we will be ready to return back the fully constructed class instance.
        if (! $needsContextualBuild) {
            $this->resolved[$abstract] = true;
        }

        array_pop($this->with);

        return $object;
    }



    /**
     * Determine if a ReflectionClass has scoping attributes applied.
     *
     * @param  ReflectionClass<object>|class-string  $reflection
     * @return "singleton"|"scoped"|null
     */
    protected function getScopedTyped(ReflectionClass|string $reflection): ?string
    {
        $className = $reflection instanceof ReflectionClass
            ? $reflection->getName()
            : $reflection;

        if (array_key_exists($className, $this->checkedForSingletonOrScopedAttributes)) {
            return $this->checkedForSingletonOrScopedAttributes[$className];
        }

        try {
            $reflection = $reflection instanceof ReflectionClass
                ? $reflection
                : new ReflectionClass($reflection);
        } catch (ReflectionException) {
            return $this->checkedForSingletonOrScopedAttributes[$className] = null;
        }

        $type = null;

        if (! empty($reflection->getAttributes(Singleton::class))) {
            $type = 'singleton';
        } elseif (! empty($reflection->getAttributes(Scoped::class))) {
            $type = 'scoped';
        }

        return $this->checkedForSingletonOrScopedAttributes[$className] = $type;
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @template TInstance of mixed
     *
     * @param callable|string $abstract
     * @param TInstance $instance
     * @return TInstance
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function instance(callable|string $abstract, mixed $instance): mixed
    {
        if (! is_string($abstract)) {
            throw new InvalidArgumentException('Container instance abstracts must be strings.');
        }

        $this->removeAbstractAlias($abstract);

        $isBound = $this->bound($abstract);

        unset($this->aliases[$abstract]);

        // We'll check to determine if this type has been bound before, and if it has
        // we will fire the rebound callbacks registered with the container and it
        // can be updated with consuming classes that have gotten resolved here.
        $this->instances[$abstract] = $instance;

        if ($isBound) {
            $this->rebound($abstract);
        }

        return $instance;
    }

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param string|class-string<TClass> $abstract
     * @return ($abstract is class-string<TClass> ? Closure(): TClass : Closure(): mixed)
     */
    public function factory(string $abstract): callable
    {
        return fn () => $this->make($abstract);
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
        $this->abstractAliases = [];
        $this->scopedInstances = [];
        $this->checkedForAttributeBindings = [];
        $this->checkedForSingletonOrScopedAttributes = [];
    }

    /**
     * Register a new before resolving callback.
     *
     * @param callable|string $abstract
     * @param callable|null $callback
     * @return void
     */
    public function beforeResolving(callable|string $abstract, ?callable $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->globalBeforeResolvingCallbacks[] = $abstract;
        } else {
            $this->beforeResolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new resolving callback.
     *
     * @param callable|string $abstract
     * @param callable|null $callback
     * @return void
     */
    public function resolving(callable|string $abstract, ?callable $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if (is_null($callback) && $abstract instanceof Closure) {
            $this->globalResolvingCallbacks[] = $abstract;
        } else {
            $this->resolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new after resolving callback.
     *
     * @param callable|string $abstract
     * @param callable|null $callback
     * @return void
     */
    public function afterResolving(callable|string $abstract, ?callable $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->globalAfterResolvingCallbacks[] = $abstract;
        } else {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Get the method binding for the given method.
     *
     * @param string $method
     * @param mixed $instance
     * @return mixed
     */
    public function callMethodBinding(string $method, mixed $instance): mixed
    {
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        return static::$container ??= new static;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param WireframeServiceContainer|null $container
     * @return WireframeServiceContainer|static|null
     */
    public static function setInstance(?WireframeServiceContainer $container = null): WireframeServiceContainer|static|null
    {
        return static::$container = $container;
    }

    public function cliMachine(): string
    {
        throw new BadMethodCallException('cliMachine is not available on Chassis; use Machine.');
    }

    public function hasDebugModeEnabled(): bool
    {
        throw new BadMethodCallException('hasDebugModeEnabled is not available on Chassis; use Machine.');
    }

    public function register(string|ServiceProvider $provider, bool $force = false): ServiceProvider
    {
        throw new BadMethodCallException('register is not available on Chassis; use Machine.');
    }

    public function resolveProvider(string $provider): ServiceProvider
    {
        throw new BadMethodCallException('resolveProvider is not available on Chassis; use Machine.');
    }
}