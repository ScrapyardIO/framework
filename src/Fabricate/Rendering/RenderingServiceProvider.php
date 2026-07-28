<?php

namespace Fabricate\Rendering;

use Fabricate\Contracts\NutsAndbolts\DeferrableProvider;
use Fabricate\Contracts\Rendering\RenderFactory as FactoryContract;
use Fabricate\NutsAndBolts\ServiceProvider;

class RenderingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->program->singleton('gfx', fn ($app) => new RenderManager($app));
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
