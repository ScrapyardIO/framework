<?php

namespace Fabricate\Process;

use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

class ProcessServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->program->singleton('process', function () {
            return new Factory;
        });

        $this->program->singleton(Factory::class, function ($app) {
            return $app->make('process');
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'process',
            Factory::class,
        ];
    }
}
