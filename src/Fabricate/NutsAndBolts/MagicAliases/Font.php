<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

/**
 * @method static void addFont(string $name, string $class_name)
 * @method static \Fabricate\Rendering\Fonts\GFXFont font(string $name)
 * @method static bool hasFont(string $name)
 * @method static array<string, class-string<\Fabricate\Rendering\Fonts\GFXFont>> listFonts()
 *
 * @see \Fabricate\Fonts\FontRegistry
 */
class Font extends MagicAlias
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getMagicAliasAccessor(): string
    {
        return 'font';
    }
}
