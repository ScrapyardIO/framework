<?php

namespace Fabricate\Gfx;

use Fabricate\Contracts\Gfx\Factory as FactoryContract;
use Fabricate\Contracts\Support\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

class RenderingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->machine->singleton('gfx', fn ($app) => new RenderManager($app));
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'gfx',
            RenderManager::class,
            FactoryContract::class,
        ];
    }
}
