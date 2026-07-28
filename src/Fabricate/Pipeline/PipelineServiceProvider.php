<?php

namespace Fabricate\Pipeline;

use Fabricate\Contracts\Core\Program;
use Fabricate\Contracts\Pipeline\Hub as PipelineHubContract;
use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

class PipelineServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->program->singleton(
            PipelineHubContract::class,
            Hub::class
        );

        $this->program->bind('pipeline', fn (Program $program) => new Pipeline($program));
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            PipelineHubContract::class,
            'pipeline',
        ];
    }
}
