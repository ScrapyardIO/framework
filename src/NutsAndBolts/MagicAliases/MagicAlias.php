<?php

namespace ScrapyardIO\NutsAndBolts\MagicAliases;

use RuntimeException;
use BareMetal\Contracts\Core\Machine;
use ScrpayardIO\NutsAndBolts\Collection;

abstract class MagicAlias
{
    /**
     * The application instance being aliased.
     */
    protected static ?Machine $app;

    /**
     * The resolved object instances.
     */
    protected static array $resolved_instance = [];

    /**
     * Indicates if the resolved instance should be cached.
     */
    protected static bool $cached = true;

    /**
     * Get the root object behind the facade.
     */
    public static function getAliasRoot(): mixed
    {
        return static::resolveFacadeInstance(static::getAliasAccessor());
    }

    /**
     * Get the registered name of the component.
     *
     * @throws RuntimeException
     */
    protected static function getAliasAccessor(): string
    {
        throw new RuntimeException('MagicAlias does not implement getAliasAccessor method.');
    }

    /**
     * Resolve the alias root instance from the chassis.
     */
    protected static function resolveFacadeInstance(string $name): mixed
    {
        if (isset(static::$resolved_instance[$name])) {
            return static::$resolved_instance[$name];
        }

        if (static::$app) {
            if (static::$cached) {
                return static::$resolved_instance[$name] = static::$app[$name];
            }

            return static::$app[$name];
        }

        return null;
    }

    /**
     * Clear every resolved instance.
     */
    public static function clearResolvedInstances(): void
    {
        static::$resolved_instance = [];
    }

    /**
     * Set the application instance.
     */
    public static function setAliasApplication(?Machine $app): void
    {
        static::$app = $app;
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @throws RuntimeException
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::getAliasRoot();

        if (! $instance) {
            throw new RuntimeException('A MagicAlias root has not been set.');
        }

        return $instance->$method(...$args);
    }

    /**
     * Get the application default aliases.
     */
    public static function defaultAliases(): Collection
    {
        return new Collection([
            'ScrapyardIO' => App::class,
            'Arr' => Arr::class,
            'Workshop' => Workshop::class,
            //'Auth' => Auth::class,
            'Broadcast' => Broadcast::class,
            //'Cache' => Cache::class,
            //'Concurrency' => Concurrency::class,
            'Config' => Config::class,
            'Date' => Date::class,
            //'DB' => DB::class,
            //'Eloquent' => Model::class,
            'Event' => Event::class,
            'File' => File::class,
            //'Gate' => Gate::class,
            'Hash' => Hash::class,
            'Log' => Log::class,
            //'Mail' => Mail::class,
            //'Notification' => Notification::class,
            //'Number' => Number::class,
            //'Password' => Password::class,
            'Process' => Process::class,
            //'Queue' => Queue::class,
            //'RateLimiter' => RateLimiter::class,
            //'Schedule' => Schedule::class,
            //'Schema' => Schema::class,
            'Storage' => Storage::class,
            'Str' => Str::class,
            //'Uri' => Uri::class,
            //'Validator' => Validator::class,
        ]);
    }
}
