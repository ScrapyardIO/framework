<?php

namespace BareMetal\Core\Setup;

use BareMetal\Core\Console\ConsoleKernel;
use BareMetal\Core\Scrapyard;
use Illuminate\Contracts\Console\Kernel;
use BareMetal\Core\Bootstrap\RegisterProviders;

class ScrapyardBuilder
{
    public function __construct(
        protected Scrapyard $app
    ) {}

    public function withKernels(): static
    {
        $this->app->singleton(Kernel::class, ConsoleKernel::class,);

        return $this;
    }

    /**
     * Register additional service providers.
     */
    public function withProviders(array $providers = [], bool $withBootstrapProviders = true): static
    {
        RegisterProviders::merge(
            $providers,
            $withBootstrapProviders
                ? $this->app->getBootstrapProvidersPath()
                : null
        );

        return $this;
    }

    public function create(): Scrapyard
    {
        return $this->app;
    }
}
