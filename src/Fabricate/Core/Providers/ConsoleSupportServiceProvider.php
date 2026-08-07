<?php

namespace Fabricate\Core\Providers;

use Fabricate\NutsAndBolts\AggregateServiceProvider;

/**
 * Aggregates Workshop CLI providers.
 *
 * Not deferred while nested providers are still reconstituting.
 */
class ConsoleSupportServiceProvider extends AggregateServiceProvider
{
    /**
     * The provider class names.
     *
     * @var array<int, class-string>
     */
    protected array $providers = [
        WorkshopServiceProvider::class,
    ];
}
