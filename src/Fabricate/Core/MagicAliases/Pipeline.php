<?php

namespace Fabricate\Core\MagicAliases;

use Closure;
use Fabricate\MagicAliases\MagicAlias;
use UnitEnum;

/**
 * @method static \Fabricate\Pipeline\Pipeline send(mixed $passable)
 * @method static \Fabricate\Pipeline\Pipeline through(mixed $pipes)
 * @method static \Fabricate\Pipeline\Pipeline pipe(mixed $pipes)
 * @method static \Fabricate\Pipeline\Pipeline via(string $method)
 * @method static mixed then(Closure $destination)
 * @method static mixed thenReturn()
 * @method static \Fabricate\Pipeline\Pipeline finally(Closure $callback)
 * @method static \Fabricate\Pipeline\Pipeline withinTransaction(string|null|UnitEnum|false $withinTransaction = null)
 * @method static \Fabricate\Pipeline\Pipeline setContainer(\Fabricate\Chassis\Contracts\WireframeServiceContainer $container)
 * @method static \Fabricate\Pipeline\Pipeline|mixed when(Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Fabricate\Pipeline\Pipeline|mixed unless(Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \Fabricate\Pipeline\Pipeline
 */
class Pipeline extends MagicAlias
{
    /**
     * Indicates if the resolved instance should be cached.
     *
     * Fresh Pipeline per Magic Alias call (matches Laravel Facade).
     *
     * @var bool
     */
    protected static bool $cached = false;

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'pipeline';
    }
}
