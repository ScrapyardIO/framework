<?php

namespace Fabricate\Core\Providers;

use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;
use Fabricate\Core\VisualManager;
use Fabricate\Displays\DisplayRegistry;
use Fabricate\Framebuffers\FramebufferManager;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Rendering\RenderManager;

class VisualServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->program->singleton('visual', fn ($program) => new VisualManager(
            $program->make(DisplayRegistry::class),
            $program->make(RenderManager::class),
            $program->make(FramebufferManager::class),
        ));
    }

    public function provides(): array
    {
        return [
            'visual',
            VisualManager::class,
        ];
    }
}
