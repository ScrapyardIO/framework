<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

use Closure;
use Fabricate\Contracts\Core\Program;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Str;
use Fabricate\NutsAndBolts\Testing\Fakes\Fake;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

abstract class MagicAlias
{
    /**
     * The application instance behind the magic alias.
     *
     * @var Program|null
     */
    protected static ?Program $program = null;

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

        if (static::$program->resolved($accessor) === true) {
            $callback(static::getMagicAliasRoot(), static::$program);
        }

        static::$program->afterResolving($accessor, function ($service, $program) use ($callback) {
            $callback($service, $program);
        });
    }

    /**
     * Convert the magic alias into a Mockery spy.
     *
     * @return \Mockery\MockInterface
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
     * @return \Mockery\MockInterface
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
     * @return \Mockery\MockInterface
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
     * @return \Mockery\MockInterface
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

        if (isset(static::$program)) {
            static::$program->instance(static::getMagicAliasAccessor(), $instance);
        }
    }

    /**
     * Determines whether a "fake" has been set as the magic alias instance.
     *
     * @return bool
     */
    public static function isFake(): bool
    {
        $name = static::getMagicAliasAccessor();

        return isset(static::$resolvedInstance[$name]) &&
            static::$resolvedInstance[$name] instanceof Fake;
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
     * @throws \RuntimeException
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

        if (static::$program) {
            if (static::$cached) {
                return static::$resolvedInstance[$name] = static::$program[$name];
            }

            return static::$program[$name];
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
     * Get the application default aliases.
     *
     * @return Collection
     */
    public static function defaultAliases(): Collection
    {
        return new Collection([
            'App' => App::class,
            'Arr' => Arr::class,
            'Workshop' => Workshop::class,
            //'Auth' => Auth::class,
            //'Benchmark' => Benchmark::class,
            //'Broadcast' => Broadcast::class,
            'Bus' => Bus::class,
            'Cache' => Cache::class,
            'Circuit' => Circuit::class,
            //'Concurrency' => Concurrency::class,
            'Config' => Config::class,
            //'Context' => Context::class,
            //'Crypt' => Crypt::class,
            'Date' => Date::class,
            'Display' => Display::class,
            //'DB' => DB::class,
            //'Eloquent' => Model::class,
            'Event' => Event::class,
            'Font' => Font::class,
            //'File' => File::class,
            //'Gate' => Gate::class,
            //'Hash' => Hash::class,
            //'Http' => Http::class,
            'Log' => Log::class,
            //'Mail' => Mail::class,
            //'Notification' => Notification::class,
            //'Number' => Number::class,
            //'Password' => Password::class,
            'Process' => Process::class,
            'Queue' => Queue::class,
            'Redis' => Redis::class,
            //'Rendering' => Rendering::class,
            //'Route' => Route::class,
            //'Schedule' => Schedule::class,
            //'Schema' => Schema::class,
            //'Actuator' => Actuator::class,
            //'Sensor' => Sensor::class,
            //'Session' => Session::class,
            'Sensor' => Sensor::class,
            'Storage' => Storage::class,
            'Str' => Str::class,
            'Visual' => Visual::class,
            //'Uri' => Uri::class,
            //'URL' => URL::class,
            //'Validator' => Validator::class,
            //'Window' => Window::class,
        ]);
    }

    /**
     * Get the application instance behind the magic alias.
     *
     * @return Program|null
     */
    public static function getMagicAliasApplication(): ?Program
    {
        return static::$program;
    }

    /**
     * Set the application instance.
     *
     * @param Program|null $program
     * @return void
     */
    public static function setMagicAliasApplication(?Program $program): void
    {
        static::$program = $program;
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