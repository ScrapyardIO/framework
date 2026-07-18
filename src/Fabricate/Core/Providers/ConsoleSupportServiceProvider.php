<?php

namespace Fabricate\Core\Providers;

use Fabricate\Contracts\Support\DeferrableProvider;
use Fabricate\NutsAndBolts\AggregateServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider implements DeferrableProvider
{
    /**
     * The provider class names.
     *
     * @var string[]
     */
    protected array $providers = [
        WorkshopServiceProvider::class,
        //MigrationServiceProvider::class,
        ComposerServiceProvider::class,
    ];
}
