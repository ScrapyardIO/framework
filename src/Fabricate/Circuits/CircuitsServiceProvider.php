<?php

namespace Fabricate\Circuits;

use Fabricate\Contracts\Core\Program;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;

class CircuitsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void {
        $this->program->singleton('circuit', fn(Program $program) => new CircuitRegistry);
    }

    public function boot(): void {

    }

    public function provides(): array {
        return ['circuit'];
    }
}