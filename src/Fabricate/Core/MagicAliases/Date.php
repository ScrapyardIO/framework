<?php

namespace Fabricate\Core\MagicAliases;

use Fabricate\MagicAliases\MagicAlias;
use RuntimeException;

/**
 * @see https://carbon.nesbot.com/docs/
 * @see https://github.com/briannesbitt/Carbon/blob/master/src/Carbon/Factory.php
 *
 * @method static \Fabricate\NutsAndBolts\Carbon now(\DateTimeZone|string|int|null $timezone = null)
 */
class Date extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     * @throws RuntimeException
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'date';
    }
}