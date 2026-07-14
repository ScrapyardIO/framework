<?php

namespace BareMetal\Sensors;

use BareMetal\Contracts\Sensors\SensorService as SensorServiceInterface;
use BareMetal\Contracts\Support\DeferrableProvider;
use BareMetal\Sensors\Acceleration\AccelerationSensorsServiceProvider;
use ScrapyardIO\NutsAndBolts\AggregateServiceProvider;

class SensorsServiceProvider extends AggregateServiceProvider implements DeferrableProvider
{
    protected array $providers = [
        AccelerationSensorsServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(SensorServiceInterface::class, fn () => new SensorService);
    }

    public function provides(): array
    {
        return array_merge(parent::provides(), [
            SensorServiceInterface::class,
        ]);
    }
}
