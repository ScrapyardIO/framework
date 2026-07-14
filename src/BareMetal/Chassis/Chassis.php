<?php

namespace BareMetal\Chassis;

use Closure;
use TypeError;
use Exception;
use ArrayAccess;
use LogicException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use ReflectionAttribute;
use ReflectionException;
use InvalidArgumentException;
use BareMetal\Chassis\Attributes\Bind;
use BareMetal\Chassis\Attributes\Scoped;
use BareMetal\Chassis\Attributes\Singleton;
use BareMetal\Contracts\Chassis\SelfBuilding;
use BareMetal\Chassis\ContextualBindingBuilder;
use BareMetal\Contracts\Chassis\ContextualAttribute;
use ScrapyardIO\NutsAndBolts\Concerns\ReflectsClosures;
use BareMetal\Contracts\Chassis\Chassis as ChassisContract;
use BareMetal\Contracts\Chassis\BindingResolutionException;
use BareMetal\Contracts\Chassis\CircularDependencyException;

class Chassis implements ArrayAccess, ChassisContract
{
    use ReflectsClosures;

    /**
     * The current globally available container (if any).
     */
    protected static ChassisContract $instance;

    /**
     * An array of the types that have been resolved.
     * @var bool[]
     */
    protected array $resolved = [];

    /**
     * The container's bindings.
     * @var array[]
     */
    protected array $bindings = [];

    /**
     * The container's method bindings.
     * @var Closure[]
     */
    protected array $method_bindings = [];

    /**
     * The container's shared instances.
     * @var object[]
     */
    protected array $instances = [];

    /**
     * The container's scoped instances.
     */
    protected array $scoped_instances = [];

    /**
     * The registered type aliases.
     */
    protected array $aliases = [];

    /**
     * The registered aliases keyed by the abstract name.
     */
    protected array $abstract_aliases = [];

    /**
     * The extension closures for services.
     */
    protected array $extenders = [];

    /**
     * The parameter override stack.
     */
    protected array $with = [];

    /**
     * The contextual binding map.
     */
    public array $contextual = [];

    /**
     * The stack of concretions currently being built.
     */
    protected array $build_stack = [];

    /**
     * Every registered tag.
     */
    protected array $tags = [];


    /**
     * Whether an abstract class has already had its attributes checked for bindings.
     *
     * @var array<class-string, true>
     */
    protected array $checked_for_attribute_bindings = [];

    /**
     * Whether a class has already been checked for Singleton or Scoped attributes.
     *
     * @var array<class-string, "scoped"|"singleton"|null>
     */
    protected array $checked_for_singleton_or_scoped_attributes = [];

    /**
     * The callback used to determine the container's environment.
     *
     * @var (callable(array<int, string>|string): bool|string)|null
     */
    protected $environment_resolver = null;

    /**
     * Every before-resolving callback by class type.
     */
    protected array $before_resolving_callbacks = [];

    /**
     * Every global before-resolving callback.
     */
    protected array $global_before_resolving_callbacks = [];

    /**
     * Every global resolving callback.
     */
    protected array $global_resolving_callbacks = [];

    /**
     * Every global after-resolving callback.
     */
    protected array $global_after_resolving_callbacks = [];

    /**
     * Every resolving callback by class type.
     */
    protected array $resolving_callbacks = [];

    /**
     * Every after resolving callback by class type.
     */
    protected array $after_resolving_callbacks = [];

    /**
     * Every  after-resolving attribute callback by class type.
     */
    protected array $after_resolving_attribute_callbacks = [];

    /**
     * Every registered rebound callback.
     */
    protected array $rebound_callbacks = [];

    /**
     * Dynamically access container services.
     */
    public function __get(string $key): mixed
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     */
    public function __set(string $key, mixed $value): void
    {
        $this[$key] = $value;
    }

    /**
     * Alias a type to a different name.
     *
     * @param  string  $abstract
     * @param  string  $alias
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

        $this->abstract_aliases[$abstract][] = $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    /**
     * Determine if the given abstract type has been bound.
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            $this->isAlias($abstract);
    }

    /**
     * Resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param string|class-string<TClass> $abstract
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
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
     * Get the globally available instance of the container.
     */
    public static function getInstance(): static
    {
        return static::$instance ??= new static;
    }

    /**
     * Determine if a given offset exists.
     * @param  string  $offset
     */
    public function offsetExists($offset): bool
    {
        return $this->bound($offset);
    }

    /**
     * Get the value at a given offset.
     * @param string $offset
     * @throws BindingResolutionException
     */
    public function offsetGet($offset): mixed
    {
        return $this->make($offset);
    }

    /**
     * Set the value at a given offset.
     * @param string $offset
     */
    public function offsetSet($offset, mixed $value): void
    {
        $this->bind($offset, $value instanceof Closure ? $value : fn () => $value);
    }

    /**
     * Unset the value at a given offset.
     * @param  string  $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->bindings[$offset], $this->instances[$offset], $this->resolved[$offset]);
    }

    /**
     * Determine the environment for the container.
     *
     * @param  array<int, string>|string  $environments
     * @return bool
     */
    public function currentEnvironmentIs(array $environments): bool
    {
        return $this->environment_resolver === null
            ? false
            : call_user_func($this->environment_resolver, $environments);
    }

    /**
     * Register a scoped binding in the container.
     * @throws TypeError
     * @throws ReflectionException
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public function scoped(callable|string $abstract, string|callable|null $concrete = null): void
    {
        $this->scoped_instances[] = $abstract;

        $this->singleton($abstract, $concrete);
    }

    /**
     * Register a shared binding in the container.
     * @throws TypeError
     * @throws ReflectionException
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public function singleton(callable|string $abstract, string|callable|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Determine if a given string is an alias.
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Register a binding with the container.
     * @throws TypeError
     * @throws ReflectionException
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     *
     */
    public function bind(callable|string $abstract, callable|string|null $concrete = null, bool $shared = false): void
    {
        if ($abstract instanceof Closure) {
            $this->bindBasedOnClosureReturnTypes(
                $abstract, $concrete, $shared
            );
            return;
        }

        $this->dropStaleInstances($abstract);

        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type. After that, the concrete type to be registered as shared
        // without being forced to state their classes in both of the parameters.
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // If the factory is not a Closure, it means it is just a class name which is
        // bound into this container to the abstract type, and we will just wrap it
        // up inside its own Closure to give us more convenience when extending.
        if (! $concrete instanceof Closure) {
            if (! is_string($concrete)) {
                throw new TypeError(self::class.'::bind(): Argument #2 ($concrete) must be of type Closure|string|null');
            }

            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = ['concrete' => $concrete, 'shared' => $shared];

        // If the abstract type was already resolved in this container we'll fire the
        // rebound listener so that any objects which have already gotten resolved
        // can have their copy of the object updated via the listener callbacks.
        if ($this->resolved($abstract)) {
            $this->rebound($abstract);
        }
    }

    /**
     * Determine if the given abstract type has been resolved.
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
     * Get the alias for an abstract if available.
     */
    public function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract])
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    /**
     * Determine if a given type is shared.
     */
    public function isShared(string $abstract): bool
    {
        if (isset($this->instances[$abstract])) {
            return true;
        }

        if (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'] === true) {
            return true;
        }

        if (! class_exists($abstract)) {
            return false;
        }

        if (($scopedType = $this->getScopedTyped($abstract)) === null) {
            return false;
        }

        if ($scopedType === 'scoped') {
            if (! in_array($abstract, $this->scoped_instances, true)) {
                $this->scoped_instances[] = $abstract;
            }
        }

        return true;
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @template TClass of object
     *
     * @param \Closure(static, array): TClass|class-string<TClass> $concrete
     * @return TClass
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function build(mixed $concrete): mixed
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof Closure) {
            $this->build_stack[] = spl_object_hash($concrete);

            try {
                return $concrete($this, $this->getLastParameterOverride());
            } finally {
                array_pop($this->build_stack);
            }
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
        }

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface or Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if (! $reflector->isInstantiable()) {
            $this->notInstantiable($concrete);
        }

        if (is_a($concrete, SelfBuilding::class, true) &&
            ! in_array($concrete, $this->build_stack, true)) {
            return $this->buildSelfBuildingInstance($concrete, $reflector);
        }

        $this->build_stack[] = $concrete;

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (is_null($constructor)) {
            array_pop($this->build_stack);

            $this->fireAfterResolvingAttributeCallbacks(
                $reflector->getAttributes(), $instance = new $concrete
            );

            return $instance;
        }

        $dependencies = $constructor->getParameters();

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        try {
            $instances = $this->resolveDependencies($dependencies);
        } finally {
            array_pop($this->build_stack);
        }

        $this->fireAfterResolvingAttributeCallbacks(
            $reflector->getAttributes(), $instance = new $concrete(...$instances)
        );

        return $instance;
    }

    /**
     * Fire every after-resolving attribute callback.
     *
     * @param  ReflectionAttribute[]  $attributes
     * @param  mixed  $object
     * @return void
     */
    public function fireAfterResolvingAttributeCallbacks(array $attributes, mixed $object): void
    {
        foreach ($attributes as $attribute) {
            if (is_a($attribute->getName(), ContextualAttribute::class, true)) {
                $instance = $attribute->newInstance();

                if (method_exists($instance, 'after')) {
                    $instance->after($instance, $object, $this);
                }
            }

            $callbacks = $this->getCallbacksForType(
                $attribute->getName(), $object, $this->after_resolving_attribute_callbacks
            );

            foreach ($callbacks as $callback) {
                $callback($attribute->newInstance(), $object, $this);
            }
        }
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function call(callable|string $callback, array $parameters = [], ?string $defaultMethod = null): mixed
    {
        $pushedToBuildStack = false;

        if (($className = $this->getClassForCallable($callback)) && ! in_array(
                $className,
                $this->build_stack,
                true
            )) {
            $this->build_stack[] = $className;

            $pushedToBuildStack = true;
        }

        $result = BoundMethod::call($this, $callback, $parameters, $defaultMethod);

        if ($pushedToBuildStack) {
            array_pop($this->build_stack);
        }

        return $result;
    }

    /**
     * Determine if the container has a method binding.
     */
    public function hasMethodBinding(string $method): bool
    {
        return isset($this->method_bindings[$method]);
    }

    /**
     * Get the method binding for the given method.
     *
     * @param  string  $method
     * @param  mixed  $instance
     * @return mixed
     */
    public function callMethodBinding(string $method, mixed $instance): mixed
    {
        return call_user_func($this->method_bindings[$method], $instance, $this);
    }

    /**
     * Assign a set of tags to a given binding.
     */
    public function tag(array|string $abstracts, mixed $tags): void
    {
        $tags = is_array($tags) ? $tags : array_slice(func_get_args(), 1);

        foreach ($tags as $tag) {
            if (! isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ((array) $abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }

    /**
     * Resolve all of the bindings for a given tag.
     *
     * @param  string  $tag
     * @return iterable
     */
    public function tagged(string $tag): iterable
    {
        if (! isset($this->tags[$tag])) {
            return [];
        }

        return new GoBackGenerator(function () use ($tag) {
            foreach ($this->tags[$tag] as $abstract) {
                yield $this->make($abstract);
            }
        }, count($this->tags[$tag]));
    }

    /**
     * Bind a callback to resolve with Container::call.
     */
    public function bindMethod(array|string $method, callable $callback): void
    {
        $this->method_bindings[$this->parseBindMethod($method)] = $callback;
    }

    /**
     * Register a binding if it hasn't already been registered.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function bindIf(callable|string $abstract, callable|string|null $concrete = null, bool $shared = false): void
    {
        if (! $this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Register a shared binding if it hasn't already been registered.
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
     * "Extend" an abstract type in the container.
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    public function extend(callable|string $abstract, callable $closure): void
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

    /**
     * Register an existing instance as shared in the container.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public function instance(callable|string $abstract, mixed $instance): mixed
    {
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
     * Add a contextual binding to the container.
     */
    public function addContextualBinding(string $concrete, callable|string $abstract, callable|string $implementation): void
    {
        $this->contextual[$concrete][$this->getAlias($abstract)] = $implementation;
    }

    public function when(array|string $concrete): ContextualBindingBuilder
    {
        $aliases = [];

        foreach (Util::arrayWrap($concrete) as $c) {
            $aliases[] = $this->getAlias($c);
        }

        return new ContextualBindingBuilder($this, $aliases);
    }

    /**
     * Get a closure to resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param  string|class-string<TClass>  $abstract
     * @return ($abstract is class-string<TClass> ? \Closure(): TClass : \Closure(): mixed)
     */
    public function factory(string $abstract): callable
    {
        return fn () => $this->make($abstract);
    }

    /**
     * Flush the container of all bindings and resolved instances.
     */
    public function flush(): void
    {
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
        $this->abstract_aliases = [];
        $this->scoped_instances = [];
        $this->checked_for_attribute_bindings = [];
        $this->checked_for_singleton_or_scoped_attributes = [];
    }

    /**
     * Register a new before resolving callback for all types.
     */
    public function beforeResolving(callable|string $abstract, ?callable $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->global_before_resolving_callbacks[] = $abstract;
        } else {
            $this->before_resolving_callbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new resolving callback.
     */
    public function resolving(callable|string $abstract, ?callable $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if (is_null($callback) && $abstract instanceof Closure) {
            $this->global_resolving_callbacks[] = $abstract;
        } else {
            $this->resolving_callbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new after resolving callback for all types.
     */
    public function afterResolving(callable|string $abstract, ?callable $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->global_after_resolving_callbacks[] = $abstract;
        } else {
            $this->after_resolving_callbacks[$abstract][] = $callback;
        }
    }

    /**
     * Get the method to be bound in class@method format.
     */
    protected function parseBindMethod(array|string $method): string
    {
        if (is_array($method)) {
            return $method[0].'@'.$method[1];
        }

        return $method;
    }

    /**
     * Remove an alias from the contextual binding alias cache.
     *
     * @param  string  $searched
     * @return void
     */
    protected function removeAbstractAlias(string $searched): void
    {
        if (! isset($this->aliases[$searched])) {
            return;
        }

        foreach ($this->abstract_aliases as $abstract => $aliases) {
            foreach ($aliases as $index => $alias) {
                if ($alias == $searched) {
                    unset($this->abstract_aliases[$abstract][$index]);
                }
            }
        }
    }

    /**
     * Determine if the given dependency has a parameter override.
     */
    protected function hasParameterOverride(ReflectionParameter $dependency): bool
    {
        return array_key_exists(
            $dependency->name, $this->getLastParameterOverride()
        );
    }

    /**
     * Get a parameter override for a dependency.
     */
    protected function getParameterOverride(ReflectionParameter $dependency): mixed
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    /**
     * Resolve every dependency from the ReflectionParameters.
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
     * Resolve a dependency based on an attribute.
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
     * Resolve a non-class hinted primitive dependency.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
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
     * @throws BindingResolutionException
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter): never
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    /**
     * Resolve a class based dependency from the container.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
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
     * Resolve a class based variadic dependency from the container.
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
     * Get the class name for the given callback, if one can be determined.
     * @throws ReflectionException
     */
    protected function getClassForCallable(callable|string $callback): string|false
    {
        if (is_callable($callback) &&
            ! ($reflector = new ReflectionFunction($callback(...)))->isAnonymous()) {
            return $reflector->getClosureScopeClass()->name ?? false;
        }

        return false;
    }

    /**
     * Instantiate a concrete instance of the given self building type.
     *
     * @template TClass of object
     *
     * @param  object{'newInstance': \Closure(static, array): TClass|class-string<TClass>}  $concrete
     * @param  ReflectionClass  $reflector
     * @return TClass
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    protected function buildSelfBuildingInstance(mixed $concrete, ReflectionClass $reflector): object
    {
        if (! method_exists($concrete, 'newInstance')) {
            throw new BindingResolutionException("No newInstance method exists for [$concrete].");
        }

        $this->build_stack[] = $concrete;

        $instance = $this->call([$concrete, 'newInstance']);

        array_pop($this->build_stack);

        $this->fireAfterResolvingAttributeCallbacks(
            $reflector->getAttributes(), $instance
        );

        return $instance;
    }

    /**
     * Throw an exception that the concrete is not instantiable.
     * @throws BindingResolutionException
     */
    protected function notInstantiable(string $concrete): void
    {
        if (! empty($this->build_stack)) {
            $previous = implode(', ', $this->build_stack);

            $message = "Target [$concrete] is not instantiable while building [$previous].";
        } else {
            $message = "Target [$concrete] is not instantiable.";
        }

        throw new BindingResolutionException($message);
    }

    /**
     * Get the last parameter override.
     */
    protected function getLastParameterOverride(): array
    {
        return count($this->with) ? array_last($this->with) : [];
    }

    /**
     * Fire every before-resolving callback.
     */
    protected function fireBeforeResolvingCallbacks(string $abstract, array $parameters = []): void
    {
        $this->fireBeforeCallbackArray($abstract, $parameters, $this->global_before_resolving_callbacks);

        foreach ($this->before_resolving_callbacks as $type => $callbacks) {
            if ($type === $abstract || is_subclass_of($abstract, $type)) {
                $this->fireBeforeCallbackArray($abstract, $parameters, $callbacks);
            }
        }
    }

    /**
     * Fire an array of callbacks with an object.
     */
    protected function fireBeforeCallbackArray(string $abstract, array $parameters, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($abstract, $parameters, $this);
        }
    }

    /**
     * Resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param string|class-string<TClass>|callable $abstract
     * @param array $parameters
     * @param bool $raise_events
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     */
    protected function resolve(string|callable $abstract, array $parameters = [], bool $raise_events = true): mixed
    {
        $abstract = $this->getAlias($abstract);

        // First we'll fire any event handlers which handle the "before" resolving of
        // specific types. This gives some hooks the chance to add various extends
        // calls to change the resolution of objects that they're interested in.
        if ($raise_events) {
            $this->fireBeforeResolvingCallbacks($abstract, $parameters);
        }

        $concrete = $this->getContextualConcrete($abstract);

        $needs_contextual_build = ! empty($parameters) || ! is_null($concrete);

        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
        if (isset($this->instances[$abstract]) && ! $needs_contextual_build) {
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
        if ($this->isShared($abstract) && ! $needs_contextual_build) {
            $this->instances[$abstract] = $object;
        }

        if ($raise_events) {
            $this->fireResolvingCallbacks($abstract, $object);
        }

        // Before returning, we will also set the resolved flag to "true" and pop off
        // the parameter overrides for this build. After those two things are done
        // we will be ready to return back the fully constructed class instance.
        if (! $needs_contextual_build) {
            $this->resolved[$abstract] = true;
        }

        array_pop($this->with);

        return $object;
    }

    /**
     * Fire every resolving callback.
     */
    protected function fireResolvingCallbacks(string $abstract, mixed $object): void
    {
        $this->fireCallbackArray($object, $this->global_resolving_callbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->resolving_callbacks)
        );

        $this->fireAfterResolvingCallbacks($abstract, $object);
    }

    /**
     * Fire all of the after resolving callbacks.
     *
     * @param  string  $abstract
     * @param  mixed  $object
     * @return void
     */
    protected function fireAfterResolvingCallbacks(string $abstract, mixed $object): void
    {
        $this->fireCallbackArray($object, $this->global_after_resolving_callbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->after_resolving_callbacks)
        );
    }

    /**
     * Get all callbacks for a given type.
     */
    protected function getCallbacksForType(string $abstract, mixed $object, array $callbacksPerType): array
    {
        $results = [];

        foreach ($callbacksPerType as $type => $callbacks) {
            if ($type === $abstract || (is_object($object) && $object instanceof $type)) {
                $results = array_merge($results, $callbacks);
            }
        }

        return $results;
    }

    /**
     * Fire an array of callbacks with an object.
     */
    protected function fireCallbackArray(mixed $object, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    /**
     * Get the extender callbacks for a given type.
     */
    protected function getExtenders(string $abstract): array
    {
        return $this->extenders[$this->getAlias($abstract)] ?? [];
    }

    /**
     * Determine if the given concrete is buildable.
     */
    protected function isBuildable(mixed $concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Get the contextual concrete binding for the given abstract.
     *
     * @param  string|callable  $abstract
     * @return \Closure|string|array|null
     */
    protected function getContextualConcrete(string|callable $abstract): callable|string|array|null
    {
        if (! is_null($binding = $this->findInContextualBindings($abstract))) {
            return $binding;
        }

        // Next we need to see if a contextual binding might be bound under an alias of the
        // given abstract type. So, we will need to check if any aliases exist with this
        // type and then spin through them and check for contextual bindings on these.
        if (empty($this->abstract_aliases[$abstract])) {
            return null;
        }

        foreach ($this->abstract_aliases[$abstract] as $alias) {
            if (! is_null($binding = $this->findInContextualBindings($alias))) {
                return $binding;
            }
        }

        return null;
    }

    /**
     * Find the concrete binding for the given abstract in the contextual binding array.
     */
    protected function findInContextualBindings(callable|string $abstract): callable|string|null
    {
        return $this->contextual[end($this->build_stack)][$abstract] ?? null;
    }

    /**
     * Fire the "rebound" callbacks for the given abstract type.
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    protected function rebound(string $abstract): void
    {
        if (! $callbacks = $this->getReboundCallbacks($abstract)) {
            return;
        }

        $instance = $this->make($abstract);

        foreach ($callbacks as $callback) {
            $callback($this, $instance);
        }
    }

    /**
     * Get the rebound callbacks for a given type.
     */
    protected function getReboundCallbacks(string $abstract): array
    {
        return $this->rebound_callbacks[$abstract] ?? [];
    }

    /**
     * Get the Closure to be used when building a type.
     */
    protected function getClosure(string $abstract, string $concrete): callable
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve(
                $concrete, $parameters, raise_events: false
            );
        };
    }

    /**
     * Drop every stale instance and alias.
     *
     * @param  string  $abstract
     * @return void
     */
    protected function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param  string|callable  $abstract
     * @return mixed
     */
    protected function getConcrete(string|callable $abstract): mixed
    {
        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        if ($this->environment_resolver === null ||
            ($this->checked_for_attribute_bindings[$abstract] ?? false) || ! is_string($abstract)) {
            return $abstract;
        }

        return $this->getConcreteBindingFromAttributes($abstract);
    }

    /**
     * Get the concrete binding for an abstract from the Bind attribute.
     *
     * @param  string  $abstract
     * @return mixed
     */
    protected function getConcreteBindingFromAttributes(string $abstract): mixed
    {
        $this->checked_for_attribute_bindings[$abstract] = true;

        try {
            $reflected = new ReflectionClass($abstract);
        } catch (ReflectionException) {
            return $abstract;
        }

        $bindAttributes = $reflected->getAttributes(Bind::class);

        if ($bindAttributes === []) {
            return $abstract;
        }

        $concrete = $maybeConcrete = null;

        foreach ($bindAttributes as $reflectedAttribute) {
            $instance = $reflectedAttribute->newInstance();

            if ($instance->environments === ['*']) {
                $maybeConcrete = $instance->concrete;

                continue;
            }

            if ($this->currentEnvironmentIs($instance->environments)) {
                $concrete = $instance->concrete;

                break;
            }
        }

        if ($maybeConcrete !== null && $concrete === null) {
            $concrete = $maybeConcrete;
        }

        if ($concrete === null) {
            return $abstract;
        }

        match ($this->getScopedTyped($reflected)) {
            'scoped' => $this->scoped($abstract, $concrete),
            'singleton' => $this->singleton($abstract, $concrete),
            null => $this->bind($abstract, $concrete),
        };

        return $this->bindings[$abstract]['concrete'];
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

        if (array_key_exists($className, $this->checked_for_singleton_or_scoped_attributes)) {
            return $this->checked_for_singleton_or_scoped_attributes[$className];
        }

        try {
            $reflection = $reflection instanceof ReflectionClass
                ? $reflection
                : new ReflectionClass($reflection);
        } catch (ReflectionException) {
            return $this->checked_for_singleton_or_scoped_attributes[$className] = null;
        }

        $type = null;

        if (! empty($reflection->getAttributes(Singleton::class))) {
            $type = 'singleton';
        } elseif (! empty($reflection->getAttributes(Scoped::class))) {
            $type = 'scoped';
        }

        return $this->checked_for_singleton_or_scoped_attributes[$className] = $type;
    }

    /**
     * Register a binding with the container based on the given Closure's return types.
     * @throws ReflectionException
     */
    protected function bindBasedOnClosureReturnTypes(callable|string $abstract, callable|string|null $concrete = null, bool $shared = false)
    {
        $abstracts = $this->closureReturnTypes($abstract);

        $concrete = $abstract;

        foreach ($abstracts as $abstract) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Set the shared instance of the container.
     */
    public static function setInstance(?ChassisContract $container = null): ChassisContract|static
    {
        return static::$instance = $container;
    }
}
