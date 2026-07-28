<?php

namespace Fabricate\Sketches;

use Fabricate\Contracts\Core\Program;
use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;
use Fabricate\Contracts\Sketches\SketchRegistry as SketchRegistryContract;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Sketches\Console\SketchCommand;
use Fabricate\Sketches\Console\SketchListCommand;

class SketchesServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The application base class used for conventional discovery.
     */
    protected string $appSketchBaseClass = \App\Sketches\Sketch::class;

    public function register(): void
    {
        $this->program->singleton(SketchRegistry::class, function (Program $program) {
            return new SketchRegistry($program);
        });

        $this->program->singleton(SketchRegistryContract::class, function (Program $program) {
            return $program->make(SketchRegistry::class);
        });

        $this->program->singleton('sketch', function (Program $program) {
            return $program->make(SketchRegistry::class);
        });

        $this->program->singleton(SketchRunner::class, fn () => new SketchRunner);

        $this->program->singleton('sketch.runner', function (Program $program) {
            return $program->make(SketchRunner::class);
        });

        $this->program->singleton(SketchCommand::class);
        $this->program->singleton(SketchListCommand::class);

        $this->commands([
            SketchCommand::class,
            SketchListCommand::class,
        ]);
    }

    public function boot(): void
    {
        $this->discoverAppSketches();
        $this->registerConfiguredSketches();
    }

    /**
     * Scan app/Sketches for concrete subclasses of the application Sketch base.
     */
    protected function discoverAppSketches(): void
    {
        $path = app_path('Sketches');

        if (! is_dir($path)) {
            return;
        }

        /** @var SketchRegistry $registry */
        $registry = $this->program->make(SketchRegistry::class);

        foreach (DiscoverSketches::within($path, $this->program->basePath(), $this->appSketchBaseClass) as $name => $class) {
            $registry->registerConvention($name, $class);
        }
    }

    /**
     * Register attributed Sketch classes listed in config/sketches.php.
     */
    protected function registerConfiguredSketches(): void
    {
        $classes = config('sketches.load', []);

        if (! is_array($classes) || $classes === []) {
            return;
        }

        /** @var SketchRegistry $registry */
        $registry = $this->program->make(SketchRegistry::class);

        foreach ($classes as $class) {
            if (! is_string($class) || $class === '') {
                continue;
            }

            $registry->register($class);
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'sketch',
            'sketch.runner',
            SketchRegistry::class,
            SketchRegistryContract::class,
            SketchRunner::class,
            SketchCommand::class,
            SketchListCommand::class,
        ];
    }
}
