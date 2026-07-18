<?php

namespace Fabricate\Core\Providers;

use Fabricate\NutsAndBolts\Composer;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Contracts\Support\DeferrableProvider;

class ComposerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->machine->singleton('composer', function ($app) {
            return new Composer($app['files'], $app->basePath());
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['composer'];
    }
}
