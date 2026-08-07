<?php

namespace Fabricate\Sketches;

use Fabricate\Contracts\Sketches\SketchKernel as SketchKernelContract;
use Fabricate\Contracts\Sketches\SketchRegistry as SketchRegistryContract;
use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Sketches\Runner\SketchKernel;

class SketchesServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Application base class used for conventional discovery under app/Runner/Sketches.
     */
    protected string $appSketchBaseClass = \App\Runner\Sketches\Sketch::class;

    public function register(): void
    {
        $this->container->singleton(SketchRegistry::class, function ($app) {
            return new SketchRegistry($app);
        });

        $this->container->singleton(SketchRegistryContract::class, function ($app) {
            return $app->make(SketchRegistry::class);
        });

        $this->container->singleton('sketch', function ($app) {
            return $app->make(SketchRegistry::class);
        });

        $this->container->singleton(SketchRunner::class, fn () => new SketchRunner);

        $this->container->singleton('sketch.runner', function ($app) {
            return $app->make(SketchRunner::class);
        });

        $this->container->singleton(SketchKernel::class, function ($app) {
            return new SketchKernel($app);
        });

        $this->container->singleton(SketchKernelContract::class, function ($app) {
            return $app->make(SketchKernel::class);
        });
    }

    public function boot(): void
    {
        $this->discoverAppSketches();
        $this->registerConfiguredSketches();
    }

    /**
     * Scan app/Runner/Sketches for concrete subclasses of the application Sketch base.
     */
    protected function discoverAppSketches(): void
    {
        $path = app_path('Runner/Sketches');

        if (! is_dir($path)) {
            return;
        }

        /** @var SketchRegistry $registry */
        $registry = $this->container->make(SketchRegistry::class);

        $basePath = method_exists($this->container, 'basePath')
            ? $this->container->basePath()
            : base_path();

        foreach (DiscoverSketches::within($path, $basePath, $this->appSketchBaseClass) as $name => $class) {
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
        $registry = $this->container->make(SketchRegistry::class);

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
            SketchKernel::class,
            SketchKernelContract::class,
        ];
    }
}
