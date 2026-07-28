<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Core\Program;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;

class DisplaysServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->program->singleton('display', fn(Program $program) => new DisplayRegistry);
    }

    public function boot(): void {}

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['display'];
    }
}