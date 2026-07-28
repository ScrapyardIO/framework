<?php

namespace Fabricate\Core\Bootstrap;

use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Core\Program;
use Fabricate\Contracts\Filesystem\FileNotFoundException;
use Fabricate\Core\AliasLoader;
use Fabricate\Core\PackageManifest;
use Fabricate\NutsAndBolts\MagicAliases\MagicAlias;

class RegisterMagicAliases
{
    /**
     * Bootstrap the given application.
     *
     * @param Program $app
     * @return void
     * @throws BindingResolutionException|FileNotFoundException
     */
    public function bootstrap(Program $app): void
    {
        MagicAlias::clearResolvedInstances();

        MagicAlias::setMagicAliasApplication($app);

        AliasLoader::getInstance(array_merge(
            MagicAlias::defaultAliases()->all(),
            $app->make('config')->get('machine.aliases', []),
            $app->make(PackageManifest::class)->aliases()
        ))->register();
    }
}
