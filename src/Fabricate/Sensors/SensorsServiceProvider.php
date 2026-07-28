<?php

namespace Fabricate\Sensors;

use Fabricate\Contracts\Core\Program;
use Fabricate\NutsAndBolts\ServiceProvider;
use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;

class SensorsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void {
        $this->program->singleton('sensor', fn(Program $program) => new SensorRegistry);
    }

    public function boot(): void {}

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['sensor'];
    }
}