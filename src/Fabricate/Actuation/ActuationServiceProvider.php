<?php

namespace Fabricate\Actuation;

use Fabricate\Contracts\Core\Program;
use Fabricate\Contracts\NutsAndBolts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;

class ActuationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'actuators');

        $this->program->singleton('actuator', fn (Program $program) => new ActuatorRegistry);
    }

    public function boot(): void
    {
        $this->publishes([
            $this->configPath() => $this->program->configPath('actuators.php'),
        ], 'actuators-config');
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['actuator'];
    }

    private function configPath(): string
    {
        return dirname(__DIR__, 3).'/config/actuators.php';
    }
}
