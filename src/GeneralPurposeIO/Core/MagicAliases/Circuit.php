<?php

namespace GeneralPurposeIO\Core\MagicAliases;

use GeneralPurposeIO\Contracts\IntegratedCircuits\IntegratedCircuit;
use Voyager\MagicAliases\MagicAlias;

/**
 * The chip catalog.
 *
 *   Circuit::addCircuit('st7789', ST7789::class);   // a chip package, from its provider boot()
 *   Circuit::conjure('st7789');                     // config/circuits/st7789.php, default_config — live
 *   Circuit::conjure('st7789', 'left');             // a named config
 *
 * @method static void addCircuit(string $slug, string $class_name)
 * @method static bool has(string $slug)
 * @method static array listCircuits()
 * @method static string resolveClass(string $slug)
 * @method static IntegratedCircuit conjure(string $slug, ?string $config = null)
 * @method static IntegratedCircuit build(string $slug, string $protocol, array $params)
 *
 * @see \GeneralPurposeIO\IntegratedCircuits\CircuitRegistry
 */
class Circuit extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return 'circuit';
    }
}
