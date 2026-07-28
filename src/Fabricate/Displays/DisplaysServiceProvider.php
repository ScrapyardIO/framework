<?php

namespace Fabricate\Displays;

use Fabricate\Contracts\Core\Program;
use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;
use Fabricate\Displays\Console\SetMainDisplayCommand;
use Fabricate\NutsAndBolts\ServiceProvider;

class DisplaysServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->program->singleton('display', fn (Program $program) => new DisplayRegistry);
        $this->program->singleton(SetMainDisplayCommand::class);

        $this->commands([
            SetMainDisplayCommand::class,
        ]);
    }

    public function boot(): void {}

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'display',
            SetMainDisplayCommand::class,
        ];
    }
}