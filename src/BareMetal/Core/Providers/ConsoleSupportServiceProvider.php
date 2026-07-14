<?php

namespace BareMetal\Core\Providers;

use BareMetal\Contracts\Support\DeferrableProvider;
use ScrapyardIO\NutsAndBolts\AggregateServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider implements DeferrableProvider
{
    /**
     * The provider class names.
     */
    protected array $providers = [
        WorkshopServiceProvider::class,
        //MigrationServiceProvider::class,
        //ComposerServiceProvider::class,
    ];
}
