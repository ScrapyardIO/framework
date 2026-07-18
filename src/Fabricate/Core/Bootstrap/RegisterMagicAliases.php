<?php

namespace Fabricate\Core\Bootstrap;

use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Core\Machine;
use Fabricate\Core\AliasLoader;
use Fabricate\Core\PackageManifest;
use Fabricate\NutsAndBolts\MagicAliases\MagicAlias;

class RegisterMagicAliases
{
    /**
     * Bootstrap the given application.
     *
     * @param Machine $app
     * @return void
     * @throws BindingResolutionException
     */
    public function bootstrap(Machine $app): void
    {
        MagicAlias::clearResolvedInstances();

        MagicAlias::setMagicAliasApplication($app);

        AliasLoader::getInstance(array_merge(
            $app->make('config')->get('machine.aliases', []),
            $app->make(PackageManifest::class)->aliases()
        ))->register();
    }
}
