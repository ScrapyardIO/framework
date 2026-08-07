<?php

namespace Fabricate\MagicAliases;

use Closure;
use Fabricate\Contracts\Chassis\ServiceContainer;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

abstract class MagicAlias
{
    /**
     * The application instance behind the magic alias.
     *
     * @var ServiceContainer|null
     */
    protected static ?ServiceContainer $container = null;

    /**
     * The resolved object instances.
     *
     * @var array
     */
    protected static array $resolvedInstance;

    /**
     * Indicates if the resolved instance should be cached.
     *
     * @var bool
     */
    protected static bool $cached = true;

    /**
     * Run a Closure when the magic alias has been resolved.
     *
     * @param  Closure  $callback
     * @return void
     */
    public static function resolved(Closure $callback): void
    {
        $accessor = static::getMagicAliasAccessor();

        if (static::$container->resolved($accessor) === true) {
            $callback(static::getMagicAliasRoot(), static::$container);
        }

        static::$container->afterResolving($accessor, function ($service, $container) use ($callback) {
            $callback($service, $container);
        });
    }

    /**
     * Convert the magic alias into a Mockery spy.
     *
     * @return MockInterface|null
     */
    public static function spy(): ?MockInterface
    {
        if (! static::isMock()) {
            $class = static::getMockableClass();

            return tap($class ? Mockery::spy($class) : Mockery::spy(), function ($spy) {
                static::swap($spy);
            });
        }

        return null;
    }

    /**
     * Initiate a partial mock on the magic alias.
     *
     * @return MockInterface
     */
    public static function partialMock(): Mockery\MockInterface
    {
        $name = static::getMagicAliasAccessor();

        $mock = static::isMock()
            ? static::$resolvedInstance[$name]
            : static::createFreshMockInstance();

        return $mock->makePartial();
    }

    /**
     * Initiate a mock expectation on the magic alias.
     *
     * @return \Mockery\Expectation
     */
    public static function shouldReceive(): Mockery\Expectation
    {
        $name = static::getMagicAliasAccessor();

        $mock = static::isMock()
            ? static::$resolvedInstance[$name]
            : static::createFreshMockInstance();

        return $mock->shouldReceive(...func_get_args());
    }

    /**
     * Initiate a mock expectation on the magic alias.
     *
     * @return \Mockery\Expectation
     */
    public static function expects(): Mockery\Expectation
    {
        $name = static::getMagicAliasAccessor();

        $mock = static::isMock()
            ? static::$resolvedInstance[$name]
            : static::createFreshMockInstance();

        return $mock->expects(...func_get_args());
    }

    /**
     * Create a fresh mock instance for the given class.
     *
     * @return MockInterface
     */
    protected static function createFreshMockInstance(): Mockery\MockInterface
    {
        return tap(static::createMock(), function ($mock) {
            static::swap($mock);

            $mock->shouldAllowMockingProtectedMethods();
        });
    }


    /**
     * Create a fresh mock instance for the given class.
     *
     * @return MockInterface
     */
    protected static function createMock(): Mockery\MockInterface
    {
        $class = static::getMockableClass();

        return $class ? Mockery::mock($class) : Mockery::mock();
    }

    /**
     * Determines whether a mock is set as the instance of the magic alias.
     *
     * @return bool
     */
    protected static function isMock(): bool
    {
        $name = static::getMagicAliasAccessor();

        return isset(static::$resolvedInstance[$name]) &&
            static::$resolvedInstance[$name] instanceof Mockery\LegacyMockInterface;
    }

    /**
     * Determines whether a "fake" has been set as the resolved instance of the magic alias.
     *
     * Referenced by fully-qualified name (not imported) so that
     * `fabricate/magic-aliases` does not take on a hard dependency on
     * the Testing surface — it is only ever loaded when present.
     *
     * @return bool
     */
    public static function isFake(): bool
    {
        $name = static::getMagicAliasAccessor();

        return isset(static::$resolvedInstance[$name]) &&
            static::$resolvedInstance[$name] instanceof \Fabricate\Testing\Fakes\Fake;
    }

    /**
     * Get the mockable class for the bound instance.
     *
     * @return string|null
     */
    protected static function getMockableClass(): ?string
    {
        if ($root = static::getMagicAliasRoot()) {
            return get_class($root);
        }

        return null;
    }

    /**
     * Hotswap the underlying instance behind the magic alias.
     *
     * @param  mixed  $instance
     * @return void
     */
    public static function swap(mixed $instance): void
    {
        static::$resolvedInstance[static::getMagicAliasAccessor()] = $instance;

        if (isset(static::$container)) {
            static::$container->instance(static::getMagicAliasAccessor(), $instance);
        }
    }

    /**
     * Get the root object behind the magic alias.
     *
     * @return mixed
     */
    public static function getMagicAliasRoot(): mixed
    {
        return static::resolveMagicAliasInstance(static::getMagicAliasAccessor());
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected static function getMagicAliasAccessor(): string
    {
        throw new RuntimeException('Magic alias does not implement getMagicAliasAccessor method.');
    }

    /**
     * Resolve the magic alias root instance from the container.
     *
     * @param string $name
     * @return mixed
     */
    protected static function resolveMagicAliasInstance(string $name): mixed
    {
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        if (static::$container) {
            if (static::$cached) {
                return static::$resolvedInstance[$name] = static::$container[$name];
            }

            return static::$container[$name];
        }

        return null;
    }

    /**
     * Clear a resolved magic alias instance.
     *
     * @param  ?string $name
     * @return void
     */
    public static function clearResolvedInstance(?string $name = null): void
    {
        unset(static::$resolvedInstance[$name ?? static::getMagicAliasAccessor()]);
    }

    /**
     * Clear all of the resolved instances.
     *
     * @return void
     */
    public static function clearResolvedInstances(): void
    {
        static::$resolvedInstance = [];
    }

    /**
     * Get the application instance behind the magic alias.
     *
     * @return ServiceContainer|null
     */
    public static function getMagicAliasApplication(): ?ServiceContainer
    {
        return static::$container;
    }

    /**
     * Set the container instance behind magic aliases.
     *
     * @param ServiceContainer|null $container
     * @return void
     */
    public static function setMagicAliasApplication(?ServiceContainer $container): void
    {
        static::$container = $container;
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     *
     * @throws RuntimeException
     */
    public static function __callStatic(string $method, array $args)
    {
        $instance = static::getMagicAliasRoot();

        if (! $instance) {
            throw new RuntimeException('A magic alias root has not been set.');
        }

        return $instance->$method(...$args);
    }
}