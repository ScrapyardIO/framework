<?php

namespace Fabricate\Core\Providers;

use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;
use Fabricate\NutsAndBolts\AggregateServiceProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider implements DeferrableProvider
{
    protected array $providers = [
        WorkshopServiceProvider::class
    ];
}
