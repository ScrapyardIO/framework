<?php

namespace GeneralPurposeIO\Digital;

use Voyager\MagicAliases\MagicAlias;
use GeneralPurposeIO\Contracts\Digital\DigitalIOCommunicationAdapter as CommunicationAdapter;

/**
 * @method static void extend(string $name, callable $callback)
 * @method static CommunicationAdapter adapter(string $name)
 */
class DigitalIO extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'gpio.digital-io';
    }
}