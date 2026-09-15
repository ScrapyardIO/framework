<?php

namespace GeneralPurposeIO\Core\MagicAliases;

use Voyager\MagicAliases\MagicAlias;
use GeneralPurposeIO\Circuits\PendingCircuit;
use GeneralPurposeIO\Contracts\Circuits\CircuitRegistry;
use GeneralPurposeIO\Contracts\Circuits\IntegratedCircuit;

/**
 * @method static PendingCircuit ic(string $slug)
 * @method static IntegratedCircuit profile(string $name)
 * @method static void addCircuit(string $name, string $class_name)
 * @method static void registerProfileCommand(string $ic, string $artisanCommand)
 * @method static string|null profileCommand(string $ic)
 * @method static array listCircuits()
 *
 * @see CircuitRegistry
 */
class Circuit extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return 'circuit';
    }
}
