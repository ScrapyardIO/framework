<?php

namespace Fabricate\Core\Bootstrap;

use Fabricate\Core\AliasLoader;
use Fabricate\Core\PackageManifest;
use Fabricate\Contracts\Core\Program;
use Fabricate\MagicAliases\MagicAlias;
use Fabricate\Contracts\Filesystem\FileNotFoundException;
use Fabricate\Chassis\Exceptions\BindingResolutionException;

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
            AliasLoader::defaultAliases()->all(),
            $app->make('config')->get('machine.aliases', []),
            $app->make(PackageManifest::class)->aliases()
        ))->register();
    }
}
