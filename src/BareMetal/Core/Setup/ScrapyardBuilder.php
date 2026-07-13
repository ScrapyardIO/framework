<?php

namespace BareMetal\Core\Setup;

use BareMetal\Core\Console\ConsoleKernel;
use Illuminate\Contracts\Console\Kernel;

use BareMetal\Core\Scrapyard;

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

    public function create(): Scrapyard
    {
        return $this->app;
    }
}
