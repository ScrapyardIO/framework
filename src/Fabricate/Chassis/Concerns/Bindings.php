<?php

namespace Fabricate\Chassis\Concerns;

use Closure;
use Fabricate\Chassis\Attributes\Bind;
use Fabricate\Chassis\ContextualBindingBuilder;
use Fabricate\Chassis\Exceptions\BindingResolutionException;
use Fabricate\Chassis\Exceptions\CircularDependencyException;
use Fabricate\Chassis\RewindableGenerator;
use Fabricate\Chassis\Util;
use Fabricate\Contracts\Chassis\ContextualBindingBuilder as ContextualBindingBuilderContract;
use Fabricate\Contracts\Chassis\SelfBuilding;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use TypeError;

trait Bindings
{
    /**
     * The container's bindings.
     *
     * @var array[]
     */
    protected array $bindings = [];

    /**
     * The contextual binding map.
     *
     * @var array[]
     */
    public array $contextual = [];

    /**
     * The contextual attribute handlers.
     *
     * @var array<class-string, callable>
     */
    public array $contextualAttributes = [];

    /**
     * The container's method bindings.
     *
     * @var array<string, callable>
     */
    protected array $methodBindings = [];

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
     * Register a binding with the container.
     *
     * @param callable|string $abstract
     * @param callable|string|null $concrete
     * @param bool $shared
     * @return void
     *
     * @throws TypeError
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
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
     * Register a binding with the container based on the given Closure's return types.
     *
     * @param Closure|string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
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
     * Get the contextual concrete binding for the given abstract.
     *
     * @param callable|string $abstract
     * @return Closure|string|array|null
     */
    protected function getContextualConcrete(callable|string $abstract): array|string|callable|null
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
    protected function findInContextualBindings(callable|string $abstract): callable|string|null
    {
        return $this->contextual[end($this->buildStack)][$abstract] ?? null;
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param callable|string $abstract
     * @return mixed
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
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
     * @throws ReflectionException|BindingResolutionException|CircularDependencyException
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
     * Throw an exception that the concrete is not instantiable.
     *
     * @param string $concrete
     * @return never
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
     * Instantiate a concrete instance of the given self building type.
     *
     * @template TClass of object
     *
     * @param object{'newInstance': Closure(static, array): TClass|class-string<TClass>} $concrete
     * @param ReflectionClass $reflector
     * @return TClass
     *
     * @throws BindingResolutionException|CircularDependencyException|ReflectionException
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
     * Instantiate a concrete instance of the given type.
     *
     * @template TClass of object
     *
     * @param Closure(static, array): TClass|class-string<TClass> $concrete
     * @return TClass
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException|ReflectionException
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
     * Assign a set of tags to a given binding.
     *
     * @param array<int, string>|string $abstracts
     * @param mixed ...$tags
     * @return void
     */
    public function tag(array|string $abstracts, mixed ...$tags): void
    {
        $tags = (count($tags) === 1 && is_array($tags[0]))
            ? $tags[0]
            : $tags;

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
     * @return iterable<int, mixed>
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
     * Bind a callback to resolve with Chassis::call.
     *
     * @param array{0: class-string|object, 1: string}|string $method
     * @param callable $callback
     * @return void
     */
    public function bindMethod(array|string $method, callable $callback): void
    {
        $this->methodBindings[$this->parseBindMethod($method)] = $callback;
    }

    /**
     * Get the method to be bound in class@method format.
     *
     * @param array{0: class-string|object, 1: string}|string $method
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
     * Register a binding if it hasn't already been registered.
     *
     * @param callable|string $abstract
     * @param callable|string|null $concrete
     * @param bool $shared
     * @return void
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     * @throws ReflectionException
     * @throws TypeError
     */
    public function bindIf(callable|string $abstract, callable|string|null $concrete = null, bool $shared = false): void
    {
        if (! $this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Add a contextual binding to the container.
     *
     * @param string $concrete
     * @param callable|string $abstract
     * @param array|callable|string $implementation
     * @return void
     */
    public function addContextualBinding(string $concrete, callable|string $abstract, array|callable|string $implementation): void
    {
        $key = is_string($abstract) ? $this->getAlias($abstract) : $abstract;

        $this->contextual[$concrete][$key] = $implementation;
    }

    /**
     * Define a contextual binding.
     *
     * @param array<int, string>|string $concrete
     * @return ContextualBindingBuilderContract
     */
    public function when(array|string $concrete): ContextualBindingBuilderContract
    {
        $aliases = [];

        foreach (Util::arrayWrap($concrete) as $c) {
            $aliases[] = $this->getAlias($c);
        }

        return new ContextualBindingBuilder($this, $aliases);
    }
}