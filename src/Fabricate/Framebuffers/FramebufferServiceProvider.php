<?php

namespace Fabricate\Framebuffers;

use Fabricate\Contracts\Framebuffers\Factory as FactoryContract;
use Fabricate\Contracts\Support\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

class FramebufferServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->machine->singleton('framebuffer', fn () => new FramebufferManager);
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'framebuffer',
            FramebufferManager::class,
            FactoryContract::class,
        ];
    }
}
