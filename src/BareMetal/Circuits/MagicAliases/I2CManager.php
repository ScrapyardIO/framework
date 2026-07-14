<?php

namespace BareMetal\Circuits\MagicAliases;

use ScrapyardIO\NutsAndBolts\MagicAliases\MagicAlias;
use BareMetal\Circuits\Managers\I2CManager as Manager;

class I2CManager extends MagicAlias
{
    protected static function getAliasAccessor(): string
    {
        return Manager::class;
    }
}
