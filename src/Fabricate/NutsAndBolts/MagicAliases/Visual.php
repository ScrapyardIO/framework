<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

/**
 * @method static \Fabricate\Core\PendingVisualPresentation display(string $type, string ...$arguments)
 * @method static \Fabricate\Contracts\Core\VisualPresentation|null main()
 *
 * @see \Fabricate\Core\VisualManager
 */
class Visual extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return 'visual';
    }
}
