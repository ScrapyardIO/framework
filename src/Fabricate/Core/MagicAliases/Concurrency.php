<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\Concurrency\ConcurrencyManager;
use Fabricate\MagicAliases\MagicAlias;

/**
 * @method static mixed driver(\UnitEnum|string|null $name = null)
 * @method static array run(\Closure|array $tasks, \Carbon\CarbonInterval|int|null $timeout = null)
 * @method static \Fabricate\NutsAndBolts\Defer\DeferredCallback defer(\Closure|array $tasks)
 * @method static string getDefaultInstance()
 * @method static void setDefaultInstance(\UnitEnum|string $name)
 * @method static \Fabricate\Concurrency\ConcurrencyManager forgetInstance(array|string|null $name = null)
 * @method static \Fabricate\Concurrency\ConcurrencyManager extend(string $name, \Closure $callback)
 * @method static \Fabricate\Concurrency\ConcurrencyManager setApplication(\Fabricate\Contracts\Core\Program $app)
 *
 * @see \Fabricate\Concurrency\ConcurrencyManager
 */
class Concurrency extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'concurrency';
    }
}
