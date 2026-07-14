<?php

namespace BareMetal\Core\Bootstrap;

use BareMetal\Contracts\Chassis\BindingResolutionException;
use BareMetal\Contracts\Core\Machine;
use BareMetal\Contracts\Filesystem\FileNotFoundException;
use BareMetal\Core\AliasLoader;
use BareMetal\Core\PackageManifest;
use ScrapyardIO\NutsAndBolts\MagicAliases\MagicAlias;

class RegisterMagicAliases
{
    /**
     * Bootstrap the given application.
     * @throws BindingResolutionException|FileNotFoundException
     */
    public function bootstrap(Machine $app): void
    {
        MagicAlias::clearResolvedInstances();

        MagicAlias::setAliasApplication($app);

        AliasLoader::getInstance(array_merge(
            MagicAlias::defaultAliases()->all(),
            $app->make('config')->get('scrapyard-io.aliases', []),
            $app->make(PackageManifest::class)->aliases()
        ))->register();
    }
}
