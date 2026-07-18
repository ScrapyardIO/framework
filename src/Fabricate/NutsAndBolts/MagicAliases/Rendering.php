<?php

namespace Fabricate\NutsAndBolts\MagicAliases;

/**
 * @method static \Fabricate\Gfx\GFXRenderDriver engine(\UnitEnum|string|null $engine = null)
 * @method static \Fabricate\Gfx\GFXRenderDriver driver(\UnitEnum|string|null $driver = null)
 * @method static \Fabricate\Gfx\RenderManager extend(string $driver, \Closure $callback)
 * @method static string|null getDefaultDriver()
 * @method static array getDrivers()
 * @method static \Fabricate\Contracts\Chassis\Chassis getContainer()
 * @method static \Fabricate\Gfx\RenderManager setContainer(\Fabricate\Contracts\Chassis\Chassis $container)
 * @method static \Fabricate\Gfx\RenderManager forgetDrivers()
 *
 * @see \Fabricate\Gfx\RenderManager
 * @see \Fabricate\Contracts\Gfx\Factory
 */
class Rendering extends MagicAlias
{
    protected static function getMagicAliasAccessor(): string
    {
        return 'gfx';
    }
}
