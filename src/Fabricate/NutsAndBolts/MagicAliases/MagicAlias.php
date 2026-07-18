<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

use Closure;
use Fabricate\Contracts\Core\Machine;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Collection;
use Fabricate\NutsAndBolts\Str;
use Mockery;
use Mockery\LegacyMockInterface;
use RuntimeException;

abstract class MagicAlias
{
    /**
     * The application instance behind the magic alias.
     *
     * @var \Fabricate\Contracts\Core\Machine|null
     */
    protected static ?Machine $app;

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
     * @param  \Closure  $callback
     * @return void
     */
    public static function resolved(Closure $callback): void
    {
        $accessor = static::getMagicAliasAccessor();

        if (static::$app->resolved($accessor) === true) {
            $callback(static::getMagicAliasRoot(), static::$app);
        }

        static::$app->afterResolving($accessor, function ($service, $app) use ($callback) {
            $callback($service, $app);
        });
    }

    /**
     * Convert the magic alias into a Mockery spy.
     *
     * @return \Mockery\MockInterface
     */
    public static function spy(): Mockery\MockInterface
    {
        if (! static::isMock()) {
            $class = static::getMockableClass();

            return tap($class ? Mockery::spy($class) : Mockery::spy(), function ($spy) {
                static::swap($spy);
            });
        }
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
               static::$resolvedInstance[$name] instanceof LegacyMockInterface;
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

        if (isset(static::$app)) {
            static::$app->instance(static::getMagicAliasAccessor(), $instance);
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

        if (static::$app) {
            if (static::$cached) {
                return static::$resolvedInstance[$name] = static::$app[$name];
            }

            return static::$app[$name];
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
            //'Blade' => Blade::class,
            //'Broadcast' => Broadcast::class,
            'Bus' => Bus::class,
            'Cache' => Cache::class,
            //'Concurrency' => Concurrency::class,
            'Config' => Config::class,
            //'Context' => Context::class,
            //'Cookie' => Cookie::class,
            //'Crypt' => Crypt::class,
            'Date' => Date::class,
            'Display' => Display::class,
            //'DB' => DB::class,
            //'Eloquent' => Model::class,
            'Event' => Event::class,
            'Framebuffer' => Framebuffer::class,
            //'File' => File::class,
            //'Gate' => Gate::class,
            'GPIO' => GPIO::class,
            //'Hash' => Hash::class,
            //'Http' => Http::class,
            //'Js' => Js::class,
            //'Lang' => Lang::class,
            'Log' => Log::class,
            //'Mail' => Mail::class,
            //'Notification' => Notification::class,
            //'Number' => Number::class,
            //'Password' => Password::class,
            //'Process' => Process::class,
            'Queue' => Queue::class,
            //'RateLimiter' => RateLimiter::class,
            //'Redirect' => Redirect::class,
            'Redis' => Redis::class,
            'Rendering' => Rendering::class,
            //'Request' => Request::class,
            //'Response' => Response::class,
            //'Route' => Route::class,
            //'Schedule' => Schedule::class,
            //'Schema' => Schema::class,
            //'Session' => Session::class,
            //'Storage' => Storage::class,
            'Str' => Str::class,
            //'Uri' => Uri::class,
            //'URL' => URL::class,
            //'Validator' => Validator::class,
            //'View' => View::class,
            //'Vite' => Vite::class,
            'Window' => Window::class,
        ]);
    }

    /**
     * Get the application instance behind the magic alias.
     *
     * @return \Fabricate\Contracts\Core\Machine|null
     */
    public static function getMagicAliasApplication(): ?Machine
    {
        return static::$app;
    }

    /**
     * Set the application instance.
     *
     * @param \Fabricate\Contracts\Core\Machine|null $app
     * @return void
     */
    public static function setMagicAliasApplication(?Machine $app): void
    {
        static::$app = $app;
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     *
     * @throws \RuntimeException
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
