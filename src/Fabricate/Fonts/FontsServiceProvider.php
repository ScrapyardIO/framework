<?php

namespace Fabricate\Fonts;

use Fabricate\Contracts\Core\Program;
use Fabricate\Contracts\Fonts\FontRegistry as FontRegistryContract;
use Fabricate\Fonts\Console\FontMakeCommand;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Rendering\Fonts\ClassicFont;

class FontsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->program->singleton(FontRegistry::class, fn () => new FontRegistry);

        $this->program->singleton(FontRegistryContract::class, function (Program $program) {
            return $program->make(FontRegistry::class);
        });

        $this->program->singleton('font', function (Program $program) {
            return $program->make(FontRegistry::class);
        });

        $this->program->singleton(FontMakeCommand::class);

        $this->commands([
            FontMakeCommand::class,
        ]);
    }

    public function boot(): void
    {
        /** @var FontRegistry $registry */
        $registry = $this->program->make(FontRegistry::class);

        $registry->addFont('classic', ClassicFont::class);

        $this->discoverAppFonts($registry);
    }

    /**
     * Scan app/Fonts for concrete GFXFont subclasses.
     */
    protected function discoverAppFonts(FontRegistry $registry): void
    {
        $path = app_path('Fonts');

        if (! is_dir($path)) {
            return;
        }

        foreach (DiscoverFonts::within($path, $this->program->basePath()) as $name => $class) {
            $registry->addFont($name, $class);
        }
    }
}
