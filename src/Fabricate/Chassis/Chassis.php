<?php

namespace Fabricate\Chassis;

use Closure;
use Exception;
use Fabricate\Chassis\Attributes\Bind;
use Fabricate\Chassis\Attributes\Scoped;
use Fabricate\Chassis\Attributes\Singleton;
use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Chassis\ContextualAttribute;
use Fabricate\Contracts\Chassis\SelfBuilding;
use InvalidArgumentException;
use TypeError;
use ArrayAccess;
use LogicException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionAttribute;
use ReflectionException;
use ReflectionParameter;
use Fabricate\NutsAndBolts\Concerns\ReflectsClosures;
use Fabricate\Contracts\Chassis\Chassis as ChassisContract;

class Chassis implements ChassisContract, ArrayAccess
{
    use ReflectsClosures;

    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static ChassisContract $instance;

    /**
     * An array of the types that have been resolved.
     *
     * @var bool[]
     */
    protected array $resolved = [];

    /**
     * The container's bindings.
     *
     * @var array[]
     */
    protected array $bindings = [];

    /**
     * The container's method bindings.
     *
     * @var Closure[]
     */
    protected array $methodBindings = [];

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
     * The extension closures for services.
     *
     * @var array[]
     */
    protected array $extenders = [];

    /**
     * Every registered tag.
     *
     * @var array[]
     */
    protected array $tags = [];

    /**
     * The stack of concretions currently being built.
     *
     * @var array[]
     */
    protected array $buildStack = [];

    /**
     * The parameter override stack.
     *
     * @var array[]
     */
    protected array $with = [];

    /**
     * The contextual binding map.
     *
     * @var array[]
     */
    public array $contextual = [];

    /**
     * The contextual attribute handlers.
     *
     * @var array[]
     */
    public array $contextualAttributes = [];

    /**
     * Whether an abstract class has already had its attributes checked for bindings.
     *
     * @var array<class-string, true>
     */
    protected array $checkedForAttributeBindings = [];

    /**
     * Whether a class has already been checked for Singleton or Scoped attributes.
     *
     * @var array<class-string, "scoped"|"singleton"|null>
     */
    protected array $checkedForSingletonOrScopedAttributes = [];

    /**
     * Every registered rebound callback.
     *
     * @var array[]
     */
    protected array $reboundCallbacks = [];

    /**
     * Every global before resolving callback.
     *
     * @var Closure[]
     */
    protected array $globalBeforeResolvingCallbacks = [];

    /**
     * Every global resolving callback.
     *
     * @var Closure[]
     */
    protected array $globalResolvingCallbacks = [];

    /**
     * Every global after resolving callback.
     *
     * @var Closure[]
     */
    protected array $globalAfterResolvingCallbacks = [];

    /**
     * Every before resolving callback by class type.
     *
     * @var array[]
     */
    protected array $beforeResolvingCallbacks = [];

    /**
     * Every resolving callback by class type.
     *
     * @var array[]
     */
    protected array $resolvingCallbacks = [];

    /**
     * Every after resolving callback by class type.
     *
     * @var array[]
     */
    protected array $afterResolvingCallbacks = [];

    /**
     * Every after resolving attribute callback by class type.
     *
     * @var array[]
     */
    protected array $afterResolvingAttributeCallbacks = [];

    /**
     * The callback used to determine the container's environment.
     *
     * @var (callable(array<int, string>|string): bool|string)|null
     */
    protected $environmentResolver = null;

    /**
     * Define a contextual binding.
     *
     * @param array|string $concrete
     * @return ContextualBindingBuilder
     */
    public function when(array|string $concrete): ContextualBindingBuilder
    {
        $aliases = [];

        foreach (Util::arrayWrap($concrete) as $c) {
            $aliases[] = $this->getAlias($c);
        }

        return new ContextualBindingBuilder($this, $aliases);
    }

    /**
     * Define a contextual binding based on an attribute.
     *
     * @param string $attribute
     * @param Closure $handler
     * @return void
     */
    public function whenHasAttribute(string $attribute, Closure $handler): void
    {
        $this->contextualAttributes[$attribute] = $handler;
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
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return $this->bound($id);
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
     * Determine if a given type is shared.
     *
     * @param string $abstract
     * @return bool
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
            if (! in_array($abstract, $this->scopedInstances, true)) {
                $this->scopedInstances[] = $abstract;
            }
        }

        return true;
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
     * Register a binding with the container.
     *
     * @param  Closure|string  $abstract
     * @param  Closure|string|null  $concrete
     * @param bool $shared
     * @return void
     *
     * @throws TypeError
     * @throws ReflectionException
     */
    public function bind($abstract, $concrete = null, bool $shared = false): void
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
        // bound into this container to the abstract type and we will just wrap it
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
     * Get the Closure to be used when building a type.
     *
     * @param string $abstract
     * @param string $concrete
     * @return Closure
     */
    protected function getClosure(string $abstract, string $concrete): Closure
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve(
                $concrete, $parameters, raiseEvents: false
            );
        };
    }

    /**
     * Determine if the container has a method binding.
     *
     * @param string $method
     * @return bool
     */
    public function hasMethodBinding(string $method): bool
    {
        return isset($this->methodBindings[$method]);
    }

    /**
     * Bind a callback to resolve with Chassis::call.
     *
     * @param array|string $method
     * @param  Closure  $callback
     * @return void
     */
    public function bindMethod(array|string $method, $callback): void
    {
        $this->methodBindings[$this->parseBindMethod($method)] = $callback;
    }

    /**
     * Get the method to be bound in class@method format.
     *
     * @param array|string $method
     * @return string
     */
    protected function parseBindMethod(array|string $method): string
    {
        if (is_array($method)) {
            return $method[0].'@'.$method[1];
        }

        return $method;
    }

    /**
     * Get the method binding for the given method.
     *
     * @param string $method
     * @param  mixed  $instance
     * @return mixed
     */
    public function callMethodBinding(string $method, mixed $instance): mixed
    {
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }

    /**
     * Add a contextual binding to the container.
     *
     * @param string $concrete
     * @param  Closure|string  $abstract
     * @param  Closure|string  $implementation
     * @return void
     */
    public function addContextualBinding(string $concrete, $abstract, $implementation): void
    {
        $this->contextual[$concrete][$this->getAlias($abstract)] = $implementation;
    }

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param Closure|string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     * @throws ReflectionException
     */
    public function bindIf($abstract, $concrete = null, bool $shared = false): void
    {
        if (! $this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Register a shared binding in the container.
     *
     * @param Closure|string $abstract
     * @param Closure|string|null $concrete
     * @return void
     * @throws ReflectionException
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
     * @throws ReflectionException
     */
    public function singletonIf($abstract, $concrete = null): void
    {
        if (! $this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    /**
     * Register a scoped binding in the container.
     *
     * @param Closure|string $abstract
     * @param Closure|string|null $concrete
     * @return void
     * @throws ReflectionException
     */
    public function scoped($abstract, $concrete = null): void
    {
        $this->scopedInstances[] = $abstract;

        $this->singleton($abstract, $concrete);
    }

    /**
     * Register a scoped binding if it hasn't already been registered.
     *
     * @param Closure|string $abstract
     * @param Closure|string|null $concrete
     * @return void
     * @throws ReflectionException
     */
    public function scopedIf($abstract, $concrete = null): void
    {
        if (! $this->bound($abstract)) {
            $this->scoped($abstract, $concrete);
        }
    }

    /**
     * Register a binding with the container based on the given Closure's return types.
     *
     * @param Closure|string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     * @throws ReflectionException
     */
    protected function bindBasedOnClosureReturnTypes(Closure|string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        $abstracts = $this->closureReturnTypes($abstract);

        $concrete = $abstract;

        foreach ($abstracts as $abstract) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * "Extend" an abstract type in the container.
     *
     * @param string $abstract
     * @param Closure|callable $closure
     * @return void
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

    /**
     * Register an existing instance as shared in the container.
     *
     * @template TInstance of mixed
     *
     * @param  string  $abstract
     * @param  TInstance  $instance
     * @return TInstance
     */
    public function instance($abstract, $instance): mixed
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
     * Assign a set of tags to a given binding.
     *
     * @param array|string $abstracts
     * @param  mixed  ...$tags
     * @return void
     */
    public function tag(array|string $abstracts, ...$tags): void
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
     * Resolve every binding for a given tag.
     *
     * @param string $tag
     * @return iterable
     */
    public function tagged(string $tag): iterable
    {
        if (! isset($this->tags[$tag])) {
            return [];
        }

        return new RewindableGenerator(function () use ($tag) {
            foreach ($this->tags[$tag] as $abstract) {
                yield $this->make($abstract);
            }
        }, count($this->tags[$tag]));
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
     * Bind a new callback to an abstract's rebind event.
     *
     * @param string $abstract
     * @param Closure $callback
     * @return mixed
     * @throws BindingResolutionException
     */
    public function rebinding(string $abstract, Closure $callback): mixed
    {
        $this->reboundCallbacks[$abstract = $this->getAlias($abstract)][] = $callback;

        if ($this->bound($abstract)) {
            return $this->make($abstract);
        }

        return null;
    }

    /**
     * Refresh an instance on the given target and method.
     *
     * @param string $abstract
     * @param mixed $target
     * @param string $method
     * @return mixed
     * @throws BindingResolutionException
     */
    public function refresh(string $abstract, mixed $target, string $method): mixed
    {
        return $this->rebinding($abstract, function ($app, $instance) use ($target, $method) {
            $target->{$method}($instance);
        });
    }

    /**
     * Fire the "rebound" callbacks for the given abstract type.
     *
     * @param string $abstract
     * @return void
     * @throws BindingResolutionException
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
     *
     * @param string $abstract
     * @return array
     */
    protected function getReboundCallbacks(string $abstract): array
    {
        return $this->reboundCallbacks[$abstract] ?? [];
    }

    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
     *
     * @param Closure $callback
     * @param array $parameters
     * @return Closure
     */
    public function wrap(Closure $callback, array $parameters = []): Closure
    {
        return fn () => $this->call($callback, $parameters);
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param callable|string $callback
     * @param  array<string, mixed>  $parameters
     * @param string|null $defaultMethod
     * @return mixed
     *
     * @throws InvalidArgumentException
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
     * Get the class name for the given callback, if one can be determined.
     *
     * @param callable|string $callback
     * @return string|false
     * @throws ReflectionException
     */
    protected function getClassForCallable(callable|string $callback): false|string
    {
        if (is_callable($callback) &&
            ! ($reflector = new ReflectionFunction($callback(...)))->isAnonymous()) {
            return $reflector->getClosureScopeClass()->name ?? false;
        }

        return false;
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
     * An alias function name for make().
     *
     * @template TClass of object
     *
     * @param callable|string|class-string<TClass> $abstract
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws BindingResolutionException
     */
    public function makeWith(callable|string $abstract, array $parameters = []): mixed
    {
        return $this->make($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container.
     *
     * @template TClass of object
     *
     * @param string|class-string<TClass> $abstract
     * @return ($abstract is class-string<TClass> ? TClass : mixed)
     *
     * @throws BindingResolutionException|CircularDependencyException
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
     * Get the concrete type for a given abstract.
     *
     * @param callable|string $abstract
     * @return mixed
     * @throws ReflectionException
     */
    protected function getConcrete(callable|string $abstract): mixed
    {
        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        if ($this->environmentResolver === null ||
            ($this->checkedForAttributeBindings[$abstract] ?? false) || ! is_string($abstract)) {
            return $abstract;
        }

        return $this->getConcreteBindingFromAttributes($abstract);
    }

    /**
     * Get the concrete binding for an abstract from the Bind attribute.
     *
     * @param string $abstract
     * @return mixed
     * @throws ReflectionException
     */
    protected function getConcreteBindingFromAttributes(string $abstract): mixed
    {
        $this->checkedForAttributeBindings[$abstract] = true;

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
     * Get the contextual concrete binding for the given abstract.
     *
     * @param callable|string $abstract
     * @return Closure|string|array|null
     */
    protected function getContextualConcrete(callable|string $abstract): array|string|Closure|null
    {
        if (! is_null($binding = $this->findInContextualBindings($abstract))) {
            return $binding;
        }

        // Next we need to see if a contextual binding might be bound under an alias of the
        // given abstract type. So, we will need to check if any aliases exist with this
        // type and then spin through them and check for contextual bindings on these.
        if (empty($this->abstractAliases[$abstract])) {
            return null;
        }

        foreach ($this->abstractAliases[$abstract] as $alias) {
            if (! is_null($binding = $this->findInContextualBindings($alias))) {
                return $binding;
            }
        }

        return null;
    }

    /**
     * Find the concrete binding for the given abstract in the contextual binding array.
     *
     * @param callable|string $abstract
     * @return Closure|string|null
     */
    protected function findInContextualBindings(callable|string $abstract): Closure|string|null
    {
        return $this->contextual[end($this->buildStack)][$abstract] ?? null;
    }

    /**
     * Determine if the given concrete is buildable.
     *
     * @param  mixed  $concrete
     * @param string $abstract
     * @return bool
     */
    protected function isBuildable(mixed $concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @template TClass of object
     *
     * @param Closure(static, array): TClass|class-string<TClass> $concrete
     * @return TClass
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public function build(Closure|string $concrete): mixed
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof Closure) {
            $this->buildStack[] = spl_object_hash($concrete);

            try {
                return $concrete($this, $this->getLastParameterOverride());
            } finally {
                array_pop($this->buildStack);
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
            ! in_array($concrete, $this->buildStack, true)) {
            return $this->buildSelfBuildingInstance($concrete, $reflector);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (is_null($constructor)) {
            array_pop($this->buildStack);

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
            array_pop($this->buildStack);
        }

        $this->fireAfterResolvingAttributeCallbacks(
            $reflector->getAttributes(), $instance = new $concrete(...$instances)
        );

        return $instance;
    }

    /**
     * Instantiate a concrete instance of the given self building type.
     *
     * @template TClass of object
     *
     * @param object{'newInstance': Closure(static, array): TClass|class-string<TClass>} $concrete
     * @param ReflectionClass $reflector
     * @return TClass
     *
     * @throws BindingResolutionException
     */
    protected function buildSelfBuildingInstance(object $concrete, ReflectionClass $reflector): object
    {
        if (! method_exists($concrete, 'newInstance')) {
            throw new BindingResolutionException("No newInstance method exists for [$concrete].");
        }

        $this->buildStack[] = $concrete;

        $instance = $this->call([$concrete, 'newInstance']);

        array_pop($this->buildStack);

        $this->fireAfterResolvingAttributeCallbacks(
            $reflector->getAttributes(), $instance
        );

        return $instance;
    }

    /**
     * Resolve every dependency from the ReflectionParameters.
     *
     * @param ReflectionParameter[] $dependencies
     * @return array
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
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
     * Get the last parameter override.
     *
     * @return array
     */
    protected function getLastParameterOverride(): array
    {
        return count($this->with) ? array_last($this->with) : [];
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
     * Resolve a class based dependency from the container.
     *
     * @param ReflectionParameter $parameter
     * @param string|null $className
     * @return mixed
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
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
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws BindingResolutionException
     * @throws CircularDependencyException
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
     * Throw an exception that the concrete is not instantiable.
     *
     * @param string $concrete
     * @return void
     *
     * @throws BindingResolutionException
     */
    protected function notInstantiable(string $concrete): never
    {
        if (! empty($this->buildStack)) {
            $previous = implode(', ', $this->buildStack);

            $message = "Target [$concrete] is not instantiable while building [$previous].";
        } else {
            $message = "Target [$concrete] is not instantiable.";
        }

        throw new BindingResolutionException($message);
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
     * Register a new before resolving callback for all types.
     *
     * @param Closure|string $abstract
     * @param Closure|callable|null $callback
     * @return void
     */
    public function beforeResolving($abstract, Closure|callable|null $callback = null): void
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
     * @param Closure|string $abstract
     * @param Closure|callable|null $callback
     * @return void
     */
    public function resolving($abstract, Closure|callable|null $callback = null): void
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
     * Register a new after resolving callback for all types.
     *
     * @param Closure|string $abstract
     * @param Closure|callable|null $callback
     * @return void
     */
    public function afterResolving($abstract, Closure|callable|null $callback = null): void
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
     * Register a new after resolving attribute callback for all types.
     *
     * @param string $attribute
     * @param Closure $callback
     * @return void
     */
    public function afterResolvingAttribute(string $attribute, Closure $callback): void
    {
        $this->afterResolvingAttributeCallbacks[$attribute][] = $callback;
    }

    /**
     * Fire every before resolving callbacks.
     *
     * @param string $abstract
     * @param array $parameters
     * @return void
     */
    protected function fireBeforeResolvingCallbacks(string $abstract, array $parameters = []): void
    {
        $this->fireBeforeCallbackArray($abstract, $parameters, $this->globalBeforeResolvingCallbacks);

        foreach ($this->beforeResolvingCallbacks as $type => $callbacks) {
            if ($type === $abstract || is_subclass_of($abstract, $type)) {
                $this->fireBeforeCallbackArray($abstract, $parameters, $callbacks);
            }
        }
    }

    /**
     * Fire an array of callbacks with an object.
     *
     * @param string $abstract
     * @param array $parameters
     * @param array $callbacks
     * @return void
     */
    protected function fireBeforeCallbackArray(string $abstract, array $parameters, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($abstract, $parameters, $this);
        }
    }

    /**
     * Fire every resolving callbacks.
     *
     * @param string $abstract
     * @param  mixed  $object
     * @return void
     */
    protected function fireResolvingCallbacks(string $abstract, mixed $object): void
    {
        $this->fireCallbackArray($object, $this->globalResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks)
        );

        $this->fireAfterResolvingCallbacks($abstract, $object);
    }

    /**
     * Fire every after resolving callbacks.
     *
     * @param string $abstract
     * @param  mixed  $object
     * @return void
     */
    protected function fireAfterResolvingCallbacks(string $abstract, mixed $object): void
    {
        $this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks);

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks)
        );
    }

    /**
     * Fire every after resolving attribute callbacks.
     *
     * @param  \ReflectionAttribute[]  $attributes
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
                $attribute->getName(), $object, $this->afterResolvingAttributeCallbacks
            );

            foreach ($callbacks as $callback) {
                $callback($attribute->newInstance(), $object, $this);
            }
        }
    }

    /**
     * Get all callbacks for a given type.
     *
     * @param string $abstract
     * @param mixed $object
     * @param array $callbacksPerType
     * @return array
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
     *
     * @param mixed $object
     * @param array $callbacks
     * @return void
     */
    protected function fireCallbackArray(mixed $object, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    /**
     * Get the name of the binding the container is currently resolving.
     *
     * @return string|null
     */
    public function currentlyResolving(): ?string
    {
        return array_last($this->buildStack) ?: null;
    }

    /**
     * Get the container's bindings.
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
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
     * Remove every extender callbacks for a given type.
     *
     * @param string $abstract
     * @return void
     */
    public function forgetExtenders(string $abstract): void
    {
        unset($this->extenders[$this->getAlias($abstract)]);
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
     * Remove a resolved instance from the instance cache.
     *
     * @param string $abstract
     * @return void
     */
    public function forgetInstance(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Clear every instance from the container.
     *
     * @return void
     */
    public function forgetInstances(): void
    {
        $this->instances = [];
    }

    /**
     * Clear every scoped instance from the container.
     *
     * @return void
     * @throws ReflectionException
     */
    public function forgetScopedInstances(): void
    {
        foreach ($this->scopedInstances as $scoped) {
            if ($scoped instanceof Closure) {
                foreach ($this->closureReturnTypes($scoped) as $type) {
                    unset($this->instances[$type]);
                }
            } else {
                unset($this->instances[$scoped]);
            }
        }
    }

    /**
     * Set the callback which determines the current container environment.
     *
     * @param (callable(array<int, string>|string): bool|string)|null $callback
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
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        return static::$instance ??= new static;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param ChassisContract|null $container
     * @return ChassisContract|static
     */
    public static function setInstance(?ChassisContract $container = null): ChassisContract|static
    {
        return static::$instance = $container;
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
     * Get the value at a given offset.
     *
     * @param string $offset
     * @throws BindingResolutionException
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
     * @throws ReflectionException
     */
    public function offsetSet($offset, mixed $value): void
    {
        $this->bind($offset, $value instanceof Closure ? $value : fn () => $value);
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
}
