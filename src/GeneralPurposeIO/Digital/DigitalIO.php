<?php

namespace GeneralPurposeIO\Digital;

use Voyager\MagicAliases\MagicAlias;

/**
 * @method static void extend(string $name, callable $callback)
 * @method static DigitalIOConnectionDriver driver(?string $name = null)
 */
class DigitalIO extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return 'gpio.digital';
    }
}
