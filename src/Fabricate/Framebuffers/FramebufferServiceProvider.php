<?php

namespace Fabricate\Framebuffers;

use Fabricate\Contracts\Framebuffers\BufferFactory as FactoryContract;
use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

class FramebufferServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->program->singleton('framebuffer', fn () => new FramebufferManager);
    }

    public function boot(): void
    {
        $this->discoverAppFramebuffers();
    }

    /**
     * Scan app/Framebuffers for #[AsFramebuffer] classes and register them.
     */
    protected function discoverAppFramebuffers(): void
    {
        $path = app_path('Framebuffers');

        if (! is_dir($path)) {
            return;
        }

        /** @var FramebufferManager $manager */
        $manager = $this->program->make('framebuffer');

        foreach (DiscoverFramebuffers::within($path, $this->program->basePath()) as $name => $class) {
            $manager->extend($name, fn (int $width, int $height, FormatSpec $formatSpec) => new $class(
                $width,
                $height,
                $formatSpec,
            ));
        }
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
