<?php

namespace Fabricate\Core\Providers;

use Fabricate\Contracts\Pipeline\Hub as PipelineHubContract;
use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Pipeline\Hub;
use Fabricate\Pipeline\Pipeline;

/**
 * Binds Pipeline Hub and `pipeline`.
 *
 * Core owns this glue — not fabricate/pipeline.
 */
class PipelineServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->container->singleton(
            PipelineHubContract::class,
            Hub::class
        );

        $this->container->bind('pipeline', fn ($app) => new Pipeline($app));
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            PipelineHubContract::class,
            'pipeline',
        ];
    }
}
